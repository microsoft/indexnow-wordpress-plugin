import "../scss/_common.scss";
import "../scss/responsiveLayout.scss";
import "../scss/App.scss";

import * as React from "react";
import { useState, useEffect } from "react";
import { GetApiKey } from "./withDashboardData";
import { Spinner, SpinnerSize } from "office-ui-fabric-react/lib/components/Spinner";

import { Header } from "./Header";
import { StartPage } from "./StartPage";
import { Dashboard } from "./Dashboard";
import { Icon } from "@fluentui/react/lib/Icon";

export const App: React.FunctionComponent = () => {
  const [hasAPIKey, setHasAPIKey] = useState(false);
  const [showLoading, setShowLoading] = useState(true);
  // variable to store banners
  const [bannerList, setBannerList] = useState<string[]>([]);

  useEffect(() => {
    const data = Promise.resolve(GetApiKey());
    data.then((response) => {
      setShowLoading(false);
      if (response && response.data) {
        setHasAPIKey(response.data.hasAPIKey);
      }
    });
  }, []);

  // Function to add new banner notification
  const addBanner = (notification: string) =>
    setBannerList([notification].concat(bannerList.slice()));

  // remove banner when close button is clicked
  const closeBannerOnClick = (
    event: React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    let bannerIndexString: string =
      (event.target as HTMLElement).dataset.index ?? "0";
    let temp: string[] = bannerList.slice();
    temp.splice(parseInt(bannerIndexString), 1);
    setBannerList(temp);
  };

  return (
    <div className="indexnow-App">
      <Header />
      <div className="indexnow-MainContainer">
        {bannerList.map((bannerItem, index) => {
          return (
            <div
              className={
                "indexnow-Banner" +
                (bannerItem.length <= 0 ? " indexnow-BannerHidden" : "") +
                (bannerItem.indexOf("Success") > -1
                  ? " indexnow-BannerSuccess"
                  : " indexnow-BannerFailure")
              }
            >
              <span>{bannerItem}</span>
              <Icon
                iconName="ChromeClose"
                className="closeIcon"
                data-index={index}
                onClick={closeBannerOnClick}
              />
            </div>
          );
        })}
        {showLoading &&
          <div>
            <Spinner
              size={SpinnerSize.large}
              className="maskSpinner"
            />
          </div>}
        {!showLoading &&
          <div>
            {!hasAPIKey && (
              <StartPage
                addBanner={addBanner}
                setAPIKeyAdded={() => {
                  setHasAPIKey(true);
                  setBannerList([]);
                }}
              />
            )}
            {hasAPIKey && <Dashboard addBanner={addBanner} />}
          </div>}
      </div>
    </div>
  );
};
