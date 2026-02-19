import "../scss/_common.scss";
import "../scss/responsiveLayout.scss";
import "../scss/App.scss";

import * as React from "react";
import { useState, useEffect } from "react";
import { GetApiKey } from "./withDashboardData";
import { Spinner, Button } from "@fluentui/react-components";

import { Header } from "./Header";
import { StartPage } from "./StartPage";
import { Dashboard } from "./Dashboard";
import { Dismiss24Regular } from "@fluentui/react-icons";

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
      } else if (response && response.error) {
        setBannerList([
          `Error : Failed to load API key — ${response.error.message || response.error.code || "Unknown error"}`
        ]);
      } else if (response && !response.ok) {
        setBannerList([
          `Error : Failed to load API key (HTTP ${response.status}). Please refresh the page or check your server.`
        ]);
      }
    }).catch((err) => {
      setShowLoading(false);
      const message =
        err instanceof TypeError
          ? "Could not connect to the server. Please check your network and try again."
          : (err?.message || "An unexpected error occurred.");
      setBannerList([`Error : Failed to load API key — ${message}`]);
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
              key={index}
              role="alert"
              className={
                "indexnow-Banner" +
                (bannerItem.length <= 0 ? " indexnow-BannerHidden" : "") +
                (bannerItem.indexOf("Success") > -1
                  ? " indexnow-BannerSuccess"
                  : " indexnow-BannerFailure")
              }
            >
              <span>{bannerItem}</span>
              <Button
                appearance="transparent"
                icon={<Dismiss24Regular />}
                className="closeIcon"
                data-index={index}
                onClick={closeBannerOnClick}
                aria-label="Dismiss notification"
                size="small"
              />
            </div>
          );
        })}
        {showLoading &&
          <div>
            <Spinner
              size="large"
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
