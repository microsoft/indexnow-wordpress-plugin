import "../scss/StartPage.scss";

import * as React from "react";
import { useEffect, useState } from "react";
import { Button } from "@fluentui/react-components";
import {
  Rocket24Regular,
  Send24Regular,
  NumberSymbol24Regular,
  ErrorCircle24Regular,
  TextBulletListLtr24Regular,
} from "@fluentui/react-icons";
import { SetApiKey } from "./withDashboardData";
import { ApiKeyRegex, StringConstants } from "../Constants";
import { GetApiKey } from "./withDashboardData";

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

  useEffect(() => {
    const data = Promise.resolve(GetApiKey());
    data.then((response) => {
      if (response && response.data) {
        setApiKey(String (response.data.APIKey));
      }
    });
  }, []);

  return (
    <div className="indexnow-StartPageContent">
      <div className="featuresSection">
        <h2 className="inlineText">What you can do with this plugin</h2>

        <div className="featuresListContainer">
          <div className="featureItem">
            <Rocket24Regular className="featureIcon" />
            <p>Automate URL submissions</p>
          </div>
          <div className="featureItem">
            <Send24Regular className="featureIcon" />
            <p>Manual URL submissions</p>
          </div>
          <div className="featureItem">
            <NumberSymbol24Regular className="featureIcon" />
            <p>View stats of submitted URLs</p>
          </div>
          <div className="featureItem">
            <ErrorCircle24Regular className="featureIcon" />
            <p>View recent submissions</p>
          </div>
          <div className="featureItem">
            <TextBulletListLtr24Regular className="featureIcon" />
            <p>Re-submit recent submissions</p>
          </div>
        </div>
      </div>

      <div className="keyEntrySection">
        <div className="keyEntryCard">
          <p>
          IndexNow, Easy to use protocol that websites can call to notify whenever website contents on any URL is updated or created allowing instant crawling, and discovery of the URL
          </p>
          <div>
            <Button
              appearance="primary"
              className="button submitButton"
              onClick={onSubmitApiKey}
              disabled={!ApiKeyRegex.test(apiKey) || apiKey.length !== 32}
            >
              Let&apos;s Get Started!
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};
