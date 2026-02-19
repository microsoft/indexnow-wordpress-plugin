'use strict';

const fs = require('fs');
const paths = require('./paths');
const getHttpsConfig = require('./getHttpsConfig');
const { ignoredFiles } = require('./utils');

const host = process.env.HOST || '0.0.0.0';
const sockHost = process.env.WDS_SOCKET_HOST;
const sockPath = process.env.WDS_SOCKET_PATH; // default: '/ws'
const sockPort = process.env.WDS_SOCKET_PORT;

module.exports = function (proxy, allowedHost) {
  const disableFirewall =
    !proxy || process.env.DANGEROUSLY_DISABLE_HOST_CHECK === 'true';

  const httpsConfig = getHttpsConfig();

  return {
    allowedHosts: disableFirewall ? 'all' : [allowedHost],
    headers: {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': '*',
      'Access-Control-Allow-Headers': '*',
    },
    compress: true,
    static: {
      directory: paths.appPublic,
      publicPath: [paths.publicUrlOrPath],
      watch: {
        ignored: ignoredFiles(paths.appSrc),
      },
    },
    client: {
      webSocketURL: {
        hostname: sockHost,
        pathname: sockPath,
        port: sockPort,
      },
      overlay: {
        errors: true,
        warnings: false,
      },
    },
    devMiddleware: {
      publicPath: paths.publicUrlOrPath.slice(0, -1),
    },
    server: httpsConfig
      ? { type: 'https', options: typeof httpsConfig === 'object' ? httpsConfig : {} }
      : undefined,
    host,
    historyApiFallback: {
      disableDotRule: true,
      index: paths.publicUrlOrPath,
    },
    proxy,
    setupMiddlewares(middlewares, devServer) {
      if (fs.existsSync(paths.proxySetup)) {
        require(paths.proxySetup)(devServer.app);
      }
      return middlewares;
    },
  };
};
