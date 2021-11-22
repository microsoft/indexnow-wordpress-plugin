import "../scss/StartPage.scss";

import * as React from "react";
import { useState } from "react";
import { PrimaryButton } from "@fluentui/react/lib/Button";
import { Icon } from "@fluentui/react/lib/Icon";
import { TextField } from "@fluentui/react/lib/TextField";
import { SetApiKey } from "./withDashboardData";
import { ApiKeyRegex, StringConstants } from "../Constants";

interface IStartPage {
  addBanner: (str: string) => void;
  setAPIKeyAdded: () => void;
}

export const StartPage: React.FunctionComponent<IStartPage> = (props) => {
  const [apiKey, setApiKey] = useState("");

  const onSubmitApiKey = (): void => {
    Promise.resolve(SetApiKey(apiKey)).then((response) => {
      if (response?.data?.error_type.length === 0) {
        props.setAPIKeyAdded();
      } else {
        props.addBanner(`Adding API key failed: ${response.data?.error_type}`);
      }
    });
  };

  return (
    <div className="bw-StartPageContent">
      <div className="featuresSection">
        <h2 className="inlineText">What you can do with this plugin</h2>

        <div className="featuresListContainer">
          <div className="featureItem">
            <Icon iconName="Rocket" className="featureIcon" />
            <p>Automate URL submissions</p>
          </div>
          <div className="featureItem">
            <Icon iconName="Send" className="featureIcon" />
            <p>Manual URL submissions</p>
          </div>
          <div className="featureItem">
            <Icon iconName="NumberField" className="featureIcon" />
            <p>View stats of submitted URLs</p>
          </div>
          <div className="featureItem">
            <Icon iconName="ErrorBadge" className="featureIcon" />
            <p>View recent submissions</p>
          </div>
          <div className="featureItem">
            <Icon iconName="BulletedList" className="featureIcon" />
            <p>Re-submit recent submissions</p>
          </div>
        </div>
      </div>

      <div className="keyEntrySection">
        <div className="keyEntryCard">
          <h3>Add API Key To Get Started</h3>
          <p>
            Add valid API key and automate URL submission by clicking on Start
            using this plugin. You can disable auto submission later from plugin
            if needed.
          </p>
          <TextField
            type="password"
            className="apiKeyTextField"
            value={apiKey}
            onChange={(event, item) => {
              setApiKey(item || "");
            }}
            placeholder="Enter 32 digit API key"
            validateOnLoad={false}
            onGetErrorMessage={() => {
              return !ApiKeyRegex.test(apiKey) || apiKey.length !== 32
                ? StringConstants.ApiKeyValidationError
                : "";
            }}
          />
          <p>
            Don"t have API key?{" "}
            <a href={StringConstants.ApiKeyHelpLink} target="_blank">
              Click here to know how to generate.
            </a>
          </p>
          <div>
            <PrimaryButton
              className="button submitButton"
              text="Start using plugin"
              onClick={onSubmitApiKey}
              disabled={!ApiKeyRegex.test(apiKey) || apiKey.length !== 32}
            />
          </div>
        </div>
      </div>
    </div>
  );
};
