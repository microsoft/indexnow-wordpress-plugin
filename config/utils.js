'use strict';

const path = require('path');
const fs = require('fs');
const zlib = require('zlib');
const chalk = require('chalk');

// Check that required files exist
function checkRequiredFiles(files) {
  let missing = false;
  files.forEach(filePath => {
    try {
      fs.accessSync(filePath, fs.constants.F_OK);
    } catch (err) {
      missing = true;
      const dirName = path.dirname(filePath);
      const fileName = path.basename(filePath);
      console.log(chalk.red('Could not find a required file.'));
      console.log(chalk.red('  Name: ') + chalk.cyan(fileName));
      console.log(chalk.red('  Searched in: ') + chalk.cyan(dirName));
    }
  });
  return !missing;
}

// Clear the terminal
function clearConsole() {
  process.stdout.write(
    process.platform === 'win32' ? '\x1B[2J\x1B[0f' : '\x1B[2J\x1B[3J\x1B[H'
  );
}

// Format webpack messages for display
function formatWebpackMessages(json) {
  const formattedErrors = (json.errors || []).map(message => {
    return typeof message === 'object' && message.message
      ? message.message
      : String(message);
  });
  const formattedWarnings = (json.warnings || []).map(message => {
    return typeof message === 'object' && message.message
      ? message.message
      : String(message);
  });
  return { errors: formattedErrors, warnings: formattedWarnings };
}

// Print build errors
function printBuildError(err) {
  const message = err != null && err.message ? err.message : err;
  console.log(typeof message === 'string' ? message : String(message));
  console.log();
}

// Get public URL or path
function getPublicUrlOrPath(isEnvDevelopment, homepage, envPublicUrl) {
  const stubDomain = 'https://create-react-app.dev';

  if (envPublicUrl) {
    envPublicUrl = envPublicUrl.endsWith('/')
      ? envPublicUrl
      : envPublicUrl + '/';
    try {
      new URL(envPublicUrl);
      return isEnvDevelopment ? new URL(envPublicUrl).pathname : envPublicUrl;
    } catch (e) {
      return envPublicUrl;
    }
  }

  if (homepage) {
    homepage = homepage.endsWith('/') ? homepage : homepage + '/';
    try {
      new URL(homepage);
      return isEnvDevelopment ? new URL(homepage).pathname : homepage;
    } catch (e) {
      return isEnvDevelopment
        ? new URL(homepage, stubDomain).pathname
        : homepage;
    }
  }

  return '/';
}

// Prepare URLs for display
function prepareUrls(protocol, host, port, pathname) {
  const formatUrl = hostname => {
    const url = new URL(`${protocol}://${hostname}:${port}`);
    url.pathname = pathname || '/';
    return url.href;
  };

  const prettyHost = host === '0.0.0.0' || host === '::' ? 'localhost' : host;
  const localUrlForTerminal = formatUrl(prettyHost);
  const localUrlForBrowser = formatUrl(prettyHost);

  let lanUrlForConfig;
  let lanUrlForTerminal;
  try {
    const interfaces = require('os').networkInterfaces();
    const lanIp = Object.values(interfaces)
      .flat()
      .find(i => i && i.family === 'IPv4' && !i.internal);
    if (lanIp) {
      lanUrlForConfig = lanIp.address;
      lanUrlForTerminal = formatUrl(lanIp.address);
    }
  } catch (e) {
    // ignore
  }

  return {
    lanUrlForConfig,
    lanUrlForTerminal,
    localUrlForTerminal,
    localUrlForBrowser,
  };
}

// Choose an available port
function choosePort(host, defaultPort) {
  const net = require('net');
  return new Promise((resolve, reject) => {
    const server = net.createServer();
    server.once('error', err => {
      if (err.code === 'EADDRINUSE') {
        console.log(
          chalk.yellow(
            `Something is already running on port ${defaultPort}. Trying port ${defaultPort + 1}...`
          )
        );
        server.close();
        choosePort(host, defaultPort + 1).then(resolve, reject);
      } else {
        reject(err);
      }
    });
    server.once('listening', () => {
      server.close(() => resolve(defaultPort));
    });
    server.listen(defaultPort, host);
  });
}

// Create webpack compiler with custom messages
function createCompiler({
  appName,
  config,
  urls,
  useTypeScript,
  tscCompileOnError,
  webpack: wp,
}) {
  let compiler;
  try {
    compiler = wp(config);
  } catch (err) {
    console.log(chalk.red('Failed to compile.'));
    console.log();
    console.log(err.message || err);
    process.exit(1);
  }

  compiler.hooks.invalid.tap('invalid', () => {
    if (process.stdout.isTTY) {
      clearConsole();
    }
    console.log('Compiling...');
  });

  compiler.hooks.done.tap('done', stats => {
    if (process.stdout.isTTY) {
      clearConsole();
    }

    const statsData = stats.toJson({
      all: false,
      warnings: true,
      errors: true,
    });
    const messages = formatWebpackMessages(statsData);
    const isSuccessful =
      !messages.errors.length && !messages.warnings.length;

    if (isSuccessful) {
      console.log(chalk.green('Compiled successfully!'));
    }
    if (isSuccessful && process.stdout.isTTY) {
      console.log();
      console.log(
        `You can now view ${chalk.bold(appName)} in the browser.`
      );
      console.log();
      if (urls.lanUrlForTerminal) {
        console.log(
          `  ${chalk.bold('Local:')}            ${urls.localUrlForTerminal}`
        );
        console.log(
          `  ${chalk.bold('On Your Network:')}  ${urls.lanUrlForTerminal}`
        );
      } else {
        console.log(`  ${urls.localUrlForTerminal}`);
      }
      console.log();
      console.log('Note that the development build is not optimized.');
      console.log(
        `To create a production build, use ${chalk.cyan('npm run build')}.`
      );
      console.log();
    }

    if (messages.errors.length) {
      if (messages.errors.length > 1) {
        messages.errors.length = 1;
      }
      console.log(chalk.red('Failed to compile.\n'));
      console.log(messages.errors.join('\n\n'));
      return;
    }

    if (messages.warnings.length) {
      console.log(chalk.yellow('Compiled with warnings.\n'));
      console.log(messages.warnings.join('\n\n'));
    }
  });

  return compiler;
}

// Prepare proxy config
function prepareProxy(proxy, appPublicFolder, servedPathname) {
  if (!proxy) return undefined;
  if (typeof proxy === 'string') {
    return [
      {
        target: proxy,
        logLevel: 'silent',
        context: function (pathname, req) {
          return (
            req.method !== 'GET' ||
            (pathname !== servedPathname &&
              !pathname.startsWith(servedPathname + '/') &&
              !/\.[a-z]+$/i.test(pathname))
          );
        },
        onProxyReq: function (proxyReq) {
          if (proxyReq.getHeader('origin')) {
            proxyReq.setHeader('origin', proxy);
          }
        },
        onError: function (err, req, res) {
          const host = req.headers && req.headers.host;
          console.log(
            chalk.red('Proxy error:') +
              ' Could not proxy request ' +
              chalk.cyan(req.url) +
              ' from ' +
              chalk.cyan(host) +
              ' to ' +
              chalk.cyan(proxy) +
              '.'
          );
          if (res.writeHead && !res.headersSent) {
            res.writeHead(500);
          }
          res.end('Proxy error: Could not proxy request.');
        },
        secure: false,
        changeOrigin: true,
        ws: true,
        xfwd: true,
      },
    ];
  }
  return proxy;
}

// Check browserslist config
function checkBrowsers(dir) {
  const packageJsonPath = path.join(dir, 'package.json');
  try {
    const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    if (pkg.browserslist) {
      return Promise.resolve(pkg.browserslist);
    }
  } catch (e) {
    // ignore
  }
  return Promise.resolve();
}

// Get ignored files pattern for watcher
function ignoredFiles(appSrc) {
  return /[\\/]node_modules[\\/]/;
}

// Escape string for use in regular expression
function escapeStringRegexp(string) {
  return string.replace(/[|\\{}()[\]^$+*?.]/g, '\\$&').replace(/-/g, '\\x2d');
}

// Strip ANSI escape codes
function stripAnsi(str) {
  return str.replace(
    /[\u001b\u009b][[()#;?]*(?:[0-9]{1,4}(?:;[0-9]{0,4})*)?[0-9A-ORZcf-nqry=><]/g,
    ''
  );
}

// Format file size
function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' kB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

// Get all files in a directory recursively
function getAllFiles(dirPath, arrayOfFiles) {
  arrayOfFiles = arrayOfFiles || [];
  const files = fs.readdirSync(dirPath);
  files.forEach(file => {
    const filePath = path.join(dirPath, file);
    if (fs.statSync(filePath).isDirectory()) {
      getAllFiles(filePath, arrayOfFiles);
    } else {
      arrayOfFiles.push(filePath);
    }
  });
  return arrayOfFiles;
}

function removeFileNameHash(buildFolder, fileName) {
  return fileName
    .replace(buildFolder, '')
    .replace(/\\/g, '/')
    .replace(
      /\/?(.*)(\.[0-9a-f]+)(\.chunk)?\.(js|css)/,
      (match, p1, p2, p3, p4) => p1 + (p3 || '') + '.' + p4
    );
}

// Measure file sizes before build
function measureFileSizesBeforeBuild(buildFolder) {
  return new Promise(resolve => {
    const sizes = {};
    if (fs.existsSync(buildFolder)) {
      getAllFiles(buildFolder)
        .filter(f => /\.(js|css)$/.test(f))
        .forEach(filePath => {
          const contents = fs.readFileSync(filePath);
          const gzipSize = zlib.gzipSync(contents).length;
          const key = removeFileNameHash(buildFolder, filePath);
          sizes[key] = gzipSize;
        });
    }
    resolve({ root: buildFolder, sizes });
  });
}

// Print file sizes after build
function printFileSizesAfterBuild(
  stats,
  previousSizes,
  buildFolder,
  maxBundleGzipSize,
  maxChunkGzipSize
) {
  const assets = (stats.toJson({ all: false, assets: true }).assets || [])
    .filter(asset => /\.(js|css)$/.test(asset.name))
    .map(asset => {
      const filePath = path.join(buildFolder, asset.name);
      let fileContents;
      try {
        fileContents = fs.readFileSync(filePath);
      } catch (e) {
        return null;
      }
      const size = zlib.gzipSync(fileContents).length;
      const previousSize =
        previousSizes.sizes[removeFileNameHash(buildFolder, filePath)] || 0;
      const difference = size - previousSize;
      const FIFTY_KB = 1024 * 50;
      let diffLabel = '';
      if (Math.abs(difference) >= FIFTY_KB) {
        const sign = difference > 0 ? '+' : '-';
        const color = difference > 0 ? chalk.red : chalk.green;
        diffLabel = ' (' + color(sign + formatFileSize(Math.abs(difference))) + ')';
      }
      return {
        folder: path.join(
          path.basename(buildFolder),
          path.dirname(asset.name)
        ),
        name: path.basename(asset.name),
        size,
        sizeLabel: formatFileSize(size) + diffLabel,
        isLarge: asset.name.includes('.chunk.')
          ? size > maxChunkGzipSize
          : size > maxBundleGzipSize,
      };
    })
    .filter(Boolean)
    .sort((a, b) => b.size - a.size);

  const longestSizeLabelLength = Math.max(
    ...assets.map(a => stripAnsi(a.sizeLabel).length)
  );

  assets.forEach(asset => {
    let sizeLabel = asset.sizeLabel;
    const sizeLength = stripAnsi(sizeLabel).length;
    if (sizeLength < longestSizeLabelLength) {
      sizeLabel += ' '.repeat(longestSizeLabelLength - sizeLength);
    }
    const color = asset.isLarge ? chalk.yellow : chalk.dim;
    console.log(
      '  ' +
        color(sizeLabel) +
        '  ' +
        chalk.dim(asset.folder + path.sep) +
        chalk.cyan(asset.name)
    );
  });
}

// Print hosting instructions
function printHostingInstructions(
  appPackage,
  publicUrl,
  publicPath,
  buildFolder,
  useYarn
) {
  if (publicPath !== '/') {
    console.log(
      `The project was built assuming it is hosted at ${chalk.green(publicPath)}.`
    );
    console.log(
      `You can control this with the ${chalk.green('homepage')} field in your ${chalk.cyan('package.json')}.`
    );
  } else {
    console.log(
      `The project was built assuming it is hosted at ${chalk.green('/')}.`
    );
    console.log(
      `You can control this with the ${chalk.green('homepage')} field in your ${chalk.cyan('package.json')}.`
    );
  }
  console.log();
  console.log(
    `The ${chalk.cyan(buildFolder)} folder is ready to be deployed.`
  );
  console.log('You may serve it with a static server:\n');
  if (useYarn) {
    console.log(`  ${chalk.cyan('yarn')} global add serve`);
  } else {
    console.log(`  ${chalk.cyan('npm')} install -g serve`);
  }
  console.log(`  ${chalk.cyan('serve')} -s ${buildFolder}`);
  console.log();
  console.log('Find out more about deployment here:\n');
  console.log(`  ${chalk.yellow('https://cra.link/deployment')}\n`);
}

// Open browser
function openBrowser(url) {
  const { exec } = require('child_process');
  if (process.platform === 'win32') {
    exec(`start "" "${url}"`);
  } else if (process.platform === 'darwin') {
    exec(`open "${url}"`);
  } else {
    exec(`xdg-open "${url}"`);
  }
}

// InterpolateHtmlPlugin - replaces %ENV_VAR% in HTML
class InterpolateHtmlPlugin {
  constructor(htmlWebpackPlugin, replacements) {
    this.htmlWebpackPlugin = htmlWebpackPlugin;
    this.replacements = replacements;
  }
  apply(compiler) {
    compiler.hooks.compilation.tap('InterpolateHtmlPlugin', compilation => {
      this.htmlWebpackPlugin
        .getHooks(compilation)
        .afterTemplateExecution.tap('InterpolateHtmlPlugin', data => {
          Object.keys(this.replacements).forEach(key => {
            data.html = data.html.replace(
              new RegExp('%' + escapeStringRegexp(key) + '%', 'g'),
              this.replacements[key]
            );
          });
          return data;
        });
    });
  }
}

// ModuleScopePlugin - prevents imports outside src/
class ModuleScopePlugin {
  constructor(appSrc, allowedFiles) {
    this.appSrcs = Array.isArray(appSrc) ? appSrc : [appSrc];
    this.allowedFiles = new Set(allowedFiles || []);
    this.allowedPaths = [...(allowedFiles || [])].map(f => path.dirname(f));
  }
  apply(resolver) {
    const { appSrcs, allowedFiles, allowedPaths } = this;
    resolver.hooks.file.tapAsync(
      'ModuleScopePlugin',
      (request, contextResolver, callback) => {
        if (!request || !request.descriptionFileRoot) {
          return callback();
        }
        if (
          request.__innerRequest_request === undefined &&
          request.__innerRequest === undefined
        ) {
          return callback();
        }
        const req =
          request.__innerRequest_request || request.__innerRequest || '';
        if (req.startsWith('.')) {
          return callback();
        }
        const requestFullPath = path.resolve(
          path.dirname(request.descriptionFileRoot),
          req
        );
        if (
          allowedFiles.has(requestFullPath) ||
          allowedPaths.some(p => requestFullPath.startsWith(p))
        ) {
          return callback();
        }
        if (
          appSrcs.every(appSrc => {
            const relative = path.relative(appSrc, requestFullPath);
            return relative.startsWith('..') || path.isAbsolute(relative);
          })
        ) {
          const scopeError = new Error(
            `You attempted to import ${req} which falls outside of the project src/ directory. ` +
              'Relative imports outside of src/ are not supported.'
          );
          Object.defineProperty(scopeError, '__module_scope_plugin', {
            value: true,
          });
          return callback(scopeError, request);
        }
        callback();
      }
    );
  }
}

module.exports = {
  checkRequiredFiles,
  clearConsole,
  formatWebpackMessages,
  printBuildError,
  getPublicUrlOrPath,
  prepareUrls,
  choosePort,
  createCompiler,
  prepareProxy,
  checkBrowsers,
  ignoredFiles,
  measureFileSizesBeforeBuild,
  printFileSizesAfterBuild,
  printHostingInstructions,
  openBrowser,
  InterpolateHtmlPlugin,
  ModuleScopePlugin,
};
