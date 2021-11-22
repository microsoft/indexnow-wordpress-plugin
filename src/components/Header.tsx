import "../scss/Header.scss";

import * as React from "react";
import { Icon } from "@fluentui/react/lib/Icon";
import { StringConstants } from "../Constants";

const logosvg = (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="52.793"
    height="21.328"
    viewBox="0 0 52.793 21.328"
  >
    <g fill="#fff" transform="translate(311.5 -796.169)">
      <path d="M-311.5,796.169l4.261,1.5v15l6-3.464-2.942-1.38-1.856-4.62,9.456,3.322v4.83l-10.656,6.146-4.263-2.371Z" />
      <g transform="translate(-290.183 800.152)">
        <path
          d="M2708.543,12811.441v-12.808h3.644a3.981,3.981,0,0,1,2.634.813,2.626,2.626,0,0,1,.974,2.116,3.115,3.115,0,0,1-.59,1.894,3.163,3.163,0,0,1-1.625,1.142v.037a3.26,3.26,0,0,1,2.072.978,3.011,3.011,0,0,1,.777,2.148,3.349,3.349,0,0,1-1.179,2.661,4.388,4.388,0,0,1-2.974,1.019Zm1.5-11.45v4.136h1.536a2.919,2.919,0,0,0,1.938-.594,2.07,2.07,0,0,0,.706-1.675q0-1.868-2.456-1.867Zm0,5.483v4.608h2.036a3.049,3.049,0,0,0,2.05-.625,2.139,2.139,0,0,0,.728-1.714q0-2.268-3.09-2.27Z"
          transform="translate(-2708.543 -12798.358)"
        />
        <path
          d="M2891.153,12795.346a.93.93,0,0,1-.669-.268.907.907,0,0,1-.277-.68.937.937,0,0,1,.947-.955.946.946,0,0,1,.684.271.955.955,0,0,1,0,1.354A.935.935,0,0,1,2891.153,12795.346Zm.715,11.179H2890.4v-9.076h1.465Z"
          transform="translate(-2880.606 -12793.443)"
        />
        <path
          d="M2974.872,12874.426h-1.464v-5.147c0-1.941-.654-2.912-2.071-2.912a2.306,2.306,0,0,0-1.818.826,3.061,3.061,0,0,0-.719,2.086v5.147h-1.465v-9.074h1.465v1.485h.036a3.234,3.234,0,0,1,2.946-1.7,2.8,2.8,0,0,1,2.3.97,4.314,4.314,0,0,1,.795,2.8Z"
          transform="translate(-2953.66 -12861.346)"
        />
        <path
          d="M3154.274,12873.6q0,5.038-4.822,5.038a6.237,6.237,0,0,1-2.909-.63l.384-1.255a4.883,4.883,0,0,0,2.508.658c2.25,0,3.375-1.175,3.375-3.567v-.991h-.036a3.308,3.308,0,0,1-3.077,1.735,3.378,3.378,0,0,1-2.746-1.156,4.864,4.864,0,0,1-1.041-3.271,5.7,5.7,0,0,1,1.121-3.708,3.747,3.747,0,0,1,3.068-1.375,2.877,2.877,0,0,1,2.675,1.483h.036v-1.264h1.465Zm-1.465-3.359v-1.277a2.613,2.613,0,0,0-.737-1.865,2.331,2.331,0,0,0-1.793-.778,2.542,2.542,0,0,0-2.125.987,4.4,4.4,0,0,0-.768,2.764,3.573,3.573,0,0,0,.737,2.37,2.379,2.379,0,0,0,1.951.914,2.464,2.464,0,0,0,1.963-.874A3.262,3.262,0,0,0,3152.809,12870.236Z"
          transform="translate(-3122.798 -12861.29)"
        />
      </g>
    </g>
  </svg>
);

const mobileLogoSvg = (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="14.92"
    height="21.328"
    viewBox="0 0 14.92 21.328"
  >
    <g fill="#fff" transform="translate(311.5 -796.169)">
      <path d="M-311.5,796.169l4.261,1.5v15l6-3.464-2.942-1.38-1.856-4.62,9.456,3.322v4.83l-10.656,6.146-4.263-2.371Z" />
    </g>
  </svg>
);

interface IRightPanel {
  title: string;
}

export const Header: React.FunctionComponent = () => {

  return (
    <>
      <header className="bw-Header">
        <div className="headerLeftElements floatLeft">
          <span className="bingLogo desktopOnly">{logosvg}</span>
          <span className="bingLogoMobile mobileOnly">{mobileLogoSvg}</span>
          <span className="pageTitle">
            URL Submission plugin
          </span>
        </div>
        <div className="headerRightElements floatRight">
          <span
            title="Help"
            onClick={() =>
              window.open(StringConstants.PluginInfoLink, "_blank")
            }
            key="headerHelp"
          >
            <span className="desktopOnly">About this plugin</span>
            <Icon iconName="Info" className="bw-Icon" />
          </span>
        </div>
      </header>
    </>
  );
};
