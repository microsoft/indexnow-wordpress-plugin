import "../scss/StartPage.scss";

import * as React from "react";
import { useEffect, useState } from "react";
import { PrimaryButton } from "@fluentui/react/lib/Button";
import { Icon } from "@fluentui/react/lib/Icon";
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
          <p>
          IndexNow, Easy to use protocol that websites can call to notify whenever website contents on any URL is updated or created allowing instant crawling, and discovery of the URL
          </p>
          <div>
            <PrimaryButton
              className="button submitButton"
              text="Let's Get Started!"
              onClick={onSubmitApiKey}
              disabled={!ApiKeyRegex.test(apiKey) || apiKey.length !== 32}
            />
          </div>
        </div>
      </div>
    </div>
  );
};
