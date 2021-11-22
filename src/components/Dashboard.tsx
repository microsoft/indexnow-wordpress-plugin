import "../scss/Dashboard.scss";

import * as React from "react";
import { useState, useEffect } from "react";
import { DefaultButton, PrimaryButton } from "@fluentui/react/lib/Button";
import { Icon } from "@fluentui/react/lib/Icon";
import {
  GetApiSettings,
  GetStats,
  GetAllSubmissions,
  RetryFailedSubmissions,
  SubmitUrl,
  UpdateAutoSubmissionsEnabled,
  SetApiKey,
  CheckApiKeyValidity,
} from "./withDashboardData";
import { ShimmeredDetailsList } from "@fluentui/react/lib/ShimmeredDetailsList";
import {
  IColumn,
  SelectionMode,
  IChoiceGroupOption,
  ChoiceGroup,
  TextField,
} from "@fluentui/react/lib/index";
import { format, formatISO } from "date-fns";
import {
  IGetStatsResponse,
  IGetApiSettingsResponse,
  IGetAllSubmissionsResponse,
  UrlSubmission,
} from "./Interfaces";
import { Card } from "./Card";
import { StringConstants, ApiKeyRegex, SubmitUrlRegex } from "../Constants";

interface IDashboardProps {
  addBanner: (str: string) => void;
}

export const Dashboard: React.FunctionComponent<IDashboardProps> = (props) => {
  enum DashboardModalState {
    Hidden = 0,
    UpdateApiKeyModal = 1,
    EditPrefAutoSubmissionModal = 2,
    SubmitUrlModal = 3,
  }

  const [apiKeyInvalid, setApiKeyInvalid] = useState<boolean>(false);
  const [apiSettings, setAPISettings] = useState<IGetApiSettingsResponse>();
  const [submissionStats, setSubmissionStats] = useState<IGetStatsResponse>();
  const [submissionsList, setSubmissionsList] = useState<
    IGetAllSubmissionsResponse
  >();

  // variables to store flyout menu states
  const [showApiKeyPopOverMenu, setShowApiKeyPopOverMenu] = useState<boolean>(
    false
  );
  const [
    showAutoSubmissionsPopOverMenu,
    setShowAutoSubmissionsPopOverMenu,
  ] = useState<boolean>();

  // variable to control modal display state
  const [modalState, setModalState] = useState<DashboardModalState>(
    DashboardModalState.Hidden
  );

  // variables storing modal UI controls state
  const [
    selectedOptionAutoSubmissions,
    setSelectedOptionAutoSubmissions,
  ] = useState<string>("enable");
  const [textFieldValueUrlSubmit, setTextFieldValueUrlSubmit] = useState<
    string
  >("");
  const [textFieldValueApiKey, setTextFieldValueApiKey] = useState<string>("");

  // variables to trigger UI data refresh
  const [urlSubmitted, setUrlSubmitted] = useState<number>(0);
  const [apiSettingsUpdated, setApiSettingsUpdated] = useState<number>(0);
  const [apiKeyUpdated, setApiKeyUpdated] = useState<number>(0);

  // Check if API key is valid
  useEffect(() => {
    Promise.resolve(CheckApiKeyValidity()).then((response) => {
      if (response && response.data) {
        if (response.data.error_type.length !== 0) {
          props.addBanner(
            "API Key Validation Error: Please check if site is verified or API key is valid to enable Automatic & Manual URL submission."
          );
          setApiKeyInvalid(true);
        }
      }
    });
  }, []);

  // Get API settings
  useEffect(() => {
    Promise.resolve(GetApiSettings()).then((response) => {
      if (response && response.data && response.data.error_type.length === 0) {
        setAPISettings(response.data);
        setSelectedOptionAutoSubmissions(
          response.data.AutoSubmissionEnabled ? "enable" : "disable"
        );
      }
    });
  }, [apiKeyUpdated, apiSettingsUpdated]);

  // Get submissions statistics
  useEffect(() => {
    Promise.resolve(GetStats()).then((response) => {
      if (response && response.data && response.data.error_type.length === 0) {
        setSubmissionStats(response.data);
      }
    });
  }, [apiKeyUpdated, urlSubmitted]);

  // Get submissions list
  useEffect(() => {
    Promise.resolve(GetAllSubmissions()).then((response) => {
      if (response && response.data && response.data.error_type.length === 0) {
        response.data.Submissions.sort((a, b) =>
          a.submission_date > b.submission_date ? -1 : 1
        );
        setSubmissionsList(response.data);
      }
    });
  }, [apiKeyUpdated, urlSubmitted]);

  // constants
  const autoSubmissionOptions: IChoiceGroupOption[] = [
    { key: "enable", text: "Enable (recommended)" },
    { key: "disable", text: "Disable" },
  ];
  const urlSubmissionTableColumns: IColumn[] = [
    {
      key: "url",
      name: "URL",
      fieldName: "url",
      onRender: (item: UrlSubmission): JSX.Element => {
        return (
          <a href={item.url} target="_blank">
            {decodeURI(item.url)}
          </a>
        );
      },
      minWidth: 250,
    },
    {
      key: "submittedOn",
      name: "Submitted On",
      fieldName: "submission_date",
      onRender: (item: UrlSubmission): string => {
        let time: Date = new Date(0);
        time.setUTCSeconds(item.submission_date);
        let dateString: string =
          time.getFullYear === new Date().getFullYear
            ? format(time, "d MMM 'at' HH':'mm", {})
            : format(time, "d MMM yyyy'at' HH':'mm", {});
        return dateString;
      },
      minWidth: 150,
    },
    {
      key: "status",
      name: "Status",
      fieldName: "error",
      onRender: (item: UrlSubmission): string => {
        return item.error === "Success" ? item.error : `Failed - ${item.error}`;
      },
      minWidth: 200,
    },
    {
      key: "resubmit",
      name: "",
      onRender: (item: UrlSubmission) => {
        return (
          <Icon
            iconName="Sync"
            data-submission={JSON.stringify(item)}
            className="bw-Icon retryIcon"
            onClick={resubmitOnClick}
          />
        );
      },
      minWidth: 40,
      maxWidth: 70,
      className: "retryColumn",
    },
  ];

  // Function handler for URL submission retries
  const resubmitOnClick = (
    event: React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    const submissionItemString: string =
      (event.target as HTMLInputElement).dataset.submission ?? "";
    const submissionItem: UrlSubmission = JSON.parse(submissionItemString);
    Promise.resolve(RetryFailedSubmissions([submissionItem])).then(
      (response) => {
        if (response && response.data) {
          setUrlSubmitted(urlSubmitted + 1);
          if (
            !response.data.hasError &&
            response.data.error_type.length === 0 &&
            response.data.SubmissionErrors.length >= 1 &&
            response.data.SubmissionErrors[0].isSubmitted
          ) {
            // add new success banner
            props.addBanner("Success : URL submitted successfully.");
          } else {
            // set failed banner
            props.addBanner(
              `Error : Submission failed for URL - ${submissionItem.url}`
            );
          }
        }
      }
    );
  };

  const onClickUpdateApiKey = (
    event: React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    setModalState(DashboardModalState.Hidden);
    Promise.resolve(SetApiKey(textFieldValueApiKey)).then((response) => {
      if (response && response.data) {
        setApiKeyUpdated(apiKeyUpdated + 1);
        if (response.data.error_type.length === 0) {
          props.addBanner("Success : API key is updated successfully.");
        } else {
          props.addBanner("Error : Unable to update API key.");
        }
      }
    });
  };

  const onClickUpdateAutoSubmissions = (
    event: React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    setModalState(DashboardModalState.Hidden);
    Promise.resolve(
      UpdateAutoSubmissionsEnabled(selectedOptionAutoSubmissions === "enable")
    ).then((response) => {
      if (response && response.data) {
        setApiSettingsUpdated(apiSettingsUpdated + 1);
        if (response.data.error_type.length === 0) {
          props.addBanner(
            "Success : Automatic URL submission preferences updated."
          );
        } else {
          props.addBanner(
            "Error : Automatic URL submission preferences not updated."
          );
        }
      }
    });
  };

  const onClickModalSubmitUrl = (
    event: React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    // hide modal and submit Url
    setModalState(DashboardModalState.Hidden);
    Promise.resolve(SubmitUrl(textFieldValueUrlSubmit)).then((response) => {
      if (response && response.data) {
        setUrlSubmitted(urlSubmitted + 1);
        if (response.data.error.length === 0) {
          // add new success banner
          props.addBanner("Success : URL submitted successfully.");
        } else {
          // set failed banner
          props.addBanner(
            `Error : Submission failed for URL - ${textFieldValueUrlSubmit}`
          );
        }
      }
    });
  };

  const downloadUrls = () => {
    let data = submissionsList?.Submissions?.map((item) => {
      let timestamp: Date = new Date(0);
      timestamp.setUTCSeconds(item.submission_date);
      return {
        url: item.url,
        timestamp: formatISO(timestamp),
        submitted: item.error === "Success",
        status: item.error,
      };
    });
    const json = JSON.stringify(data);
    const blob = new Blob([json], { type: "application/json" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "submissionslist.json";
    link.click();
  };

  return (
    <>
      <div
        className={
          "bw-DashboardContent" +
          (modalState !== DashboardModalState.Hidden ? " darken" : "")
        }
      >
        <div className="bw-CardRow">
          <div className="bw-CardColumn bw-CardColumn-2 bw-ApiKeyCard">
            <Card
              title="API Key"
              tooltip="API Key is a unique identifier that is used to authenticate API requests."
              leadingIconName="Permissions"
              className="bw-Card-WithPopOver"
            >
              <p className="cardDescription">
                ********************************
              </p>
            </Card>
            <div
              className="bw-PopOverMenu"
              onMouseEnter={() => {
                setShowApiKeyPopOverMenu(true);
              }}
              onMouseLeave={() => {
                setShowApiKeyPopOverMenu(false);
              }}
            >
              <Icon iconName="MoreVertical" className="moreIcon" />
              <div className="popOverContainer">
                <ul
                  className={
                    "popOverPanel" +
                    (showApiKeyPopOverMenu ? " openPopOverMenu" : "")
                  }
                >
                  <li
                    onClick={() => {
                      // reset UI controls and display modal
                      setTextFieldValueApiKey("");
                      setModalState(DashboardModalState.UpdateApiKeyModal);
                    }}
                  >
                    Update key
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div className="bw-CardColumn bw-CardColumn-2">
            <Card
              title="Automate URL submission"
              className={
                "bw-Card-WithPopOver " + (apiKeyInvalid ? "bw-Disabled" : "")
              }
              tooltip="This feature allows to configure automation to submit new, updated & deleted URLs to Bing and stay updated."
              leadingIconName="Rocket"
            >
              <p className="cardDescription">
                {apiSettings
                  ? apiSettings.AutoSubmissionEnabled
                    ? "Enabled"
                    : "Disabled"
                  : "-"}
              </p>
            </Card>
            <div
              className={
                "bw-PopOverMenu " + (apiKeyInvalid ? "bw-Disabled" : "")
              }
              onMouseEnter={() => {
                // don't show popover menu if API key is invalid
                !apiKeyInvalid && setShowAutoSubmissionsPopOverMenu(true);
              }}
              onMouseLeave={() => {
                setShowAutoSubmissionsPopOverMenu(false);
              }}
            >
              <Icon iconName="MoreVertical" className="moreIcon" />
              <div className="popOverContainer">
                <ul
                  className={
                    "popOverPanel" +
                    (showAutoSubmissionsPopOverMenu ? " openPopOverMenu" : "")
                  }
                >
                  <li
                    onClick={() => {
                      // reset UI controls settings and display modal
                      setSelectedOptionAutoSubmissions(
                        apiSettings?.AutoSubmissionEnabled
                          ? "enable"
                          : "disable"
                      );
                      setModalState(
                        DashboardModalState.EditPrefAutoSubmissionModal
                      );
                    }}
                  >
                    Edit preference
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div className="bw-CardRow">
          <div className="bw-CardColumn bw-CardColumn-1 bw-ManualURLSubmissionCard">
            <Card
              title="Manual URL submission"
              tooltip="This feature allows you to submit a URL directly into the Bing index."
              leadingIconName="Send"
              className={apiKeyInvalid ? "bw-Disabled" : ""}
            >
              <p className="cardDescription">
                This feature allows you to submit a URL directly into the Bing
                index.
              </p>
              <DefaultButton
                disabled={apiKeyInvalid}
                onClick={() => {
                  // reset UI controls and display modal
                  setTextFieldValueUrlSubmit("");
                  setModalState(DashboardModalState.SubmitUrlModal);
                }}
                className="buttonSubmitUrl"
                text="Submit URL"
              />
            </Card>
          </div>
        </div>

        <h2 className="sectionTitle">Overview</h2>
        <div className="bw-CardRow">
          <div className="bw-OverviewSection">
            <div className="infoCards">
              <h4>Quota left for the day</h4>
              <h2>
                {submissionStats && submissionStats.Quota !== null
                  ? submissionStats.Quota
                  : "-"}
              </h2>
              <p>(Resets at 00:00 GMT)</p>
            </div>
            <div className="infoCards">
              <h4>Successful submissions</h4>
              <h2>
                {submissionStats &&
                submissionStats.PassedSubmissionCount !== null
                  ? submissionStats.PassedSubmissionCount
                  : "-"}
              </h2>
              <p>In last 48 hours</p>
            </div>
            <div className="infoCards">
              <h4>Failed submissions</h4>
              <h2>
                {submissionStats &&
                submissionStats.FailedSubmissionCount !== null
                  ? submissionStats.FailedSubmissionCount
                  : "-"}
              </h2>
              <p>In last 48 hours</p>
            </div>
          </div>
        </div>

        <div className="sectionTitleContainer">
          <h2 className="sectionTitle">URLs submitted</h2>
          <DefaultButton
            disabled={
              submissionsList?.Submissions === null ||
              submissionsList?.Submissions.length === 0
            }
            onClick={downloadUrls}
            className="buttonUrlSubmissionsDownload"
            text="Download"
          />
        </div>
        <div className="bw-CardRow">
          <div className="bw-CardColumn bw-CardColumn-1 bw-UrlSubmissions">
            <ShimmeredDetailsList
              setKey="items"
              items={submissionsList?.Submissions ?? []}
              columns={urlSubmissionTableColumns}
              selectionMode={SelectionMode.none}
              enableShimmer={submissionsList === undefined}
              ariaLabelForShimmer="Content is being fetched"
              ariaLabelForGrid="Item details"
              listProps={{ renderedWindowsAhead: 0, renderedWindowsBehind: 0 }}
              onRenderCheckbox={(props) => {
                return props?.checked ? (
                  <Icon iconName="CheckboxComposite" className="" />
                ) : (
                  <Icon iconName="Checkbox" className="" />
                );
              }}
            />
          </div>
        </div>

        <div className="footnotes">
          <p className="footnotes">
            Maximum of 20 successful and 20 failed submissions in last 48hrs
            will be displayed.
          </p>
          <p>
            For more information, login to{" "}
            <a href={StringConstants.BingWebmasterLink}>Bing Webmaster Tools</a>
            .
          </p>
        </div>
      </div>
      <div
        className={
          "bw-Modal" +
          (modalState !== DashboardModalState.Hidden ? " showModal" : "")
        }
      >
        {modalState === DashboardModalState.UpdateApiKeyModal && (
          <div className={"modalContainer bw-ModalUpdateApiKey"}>
            <div className="modalHeader">
              <p className="modalTitle">Update API Key</p>
              <Icon
                iconName="ChromeClose"
                className="bw-Icon modalClose"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
            <div className="modalContent">
              <TextField
                placeholder="Enter 32 digit API key"
                className="textField"
                value={textFieldValueApiKey}
                onChange={(event, val) => {
                  setTextFieldValueApiKey(val || "");
                }}
                validateOnLoad={false}
                onGetErrorMessage={() => {
                  return !ApiKeyRegex.test(textFieldValueApiKey) ||
                    textFieldValueApiKey.length !== 32
                    ? StringConstants.ApiKeyValidationError
                    : "";
                }}
              />
              <p>
                Don’t have API key?{" "}
                <a href={StringConstants.ApiKeyHelpLink}>
                  Click here to know how to generate.
                </a>
              </p>
            </div>
            <div className="modalFooter">
              <PrimaryButton
                className="button primaryButton"
                text="Update"
                onClick={onClickUpdateApiKey}
                disabled={
                  !ApiKeyRegex.test(textFieldValueApiKey) ||
                  textFieldValueApiKey.length !== 32
                }
              />
              <DefaultButton
                className="button secondaryButton"
                text="Cancel"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
          </div>
        )}
        {modalState === DashboardModalState.EditPrefAutoSubmissionModal && (
          <div className="modalContainer bw-ModalEditPreferenceAutoSubmissions">
            <div className="modalHeader">
              <p className="modalTitle">
                Edit preference for Automate URL Submission
              </p>
              <Icon
                iconName="ChromeClose"
                className="bw-Icon modalClose"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
            <div className="modalContent">
              <p className="modalDescription">
                We recommend you to enable automation to submit new, updated &
                deleted URLs to Bing and stay updated.
              </p>
              <ChoiceGroup
                selectedKey={selectedOptionAutoSubmissions}
                options={autoSubmissionOptions}
                onChange={(event, option) => {
                  if (option !== undefined) {
                    setSelectedOptionAutoSubmissions(option.key);
                  }
                }}
              />
            </div>
            <div className="modalFooter">
              <PrimaryButton
                className="button primaryButton"
                text="Save"
                onClick={onClickUpdateAutoSubmissions}
                disabled={
                  (apiSettings?.AutoSubmissionEnabled
                    ? "enable"
                    : "disable") === selectedOptionAutoSubmissions
                }
              />
              <DefaultButton
                className="button secondaryButton"
                text="Cancel"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
          </div>
        )}

        {modalState === DashboardModalState.SubmitUrlModal && (
          <div className="modalContainer bw-ModalUrlSubmit">
            <div className="modalHeader">
              <p className="modalTitle">Manual URL submission</p>
              <Icon
                iconName="ChromeClose"
                className="bw-Icon modalClose"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
            <div className="modalContent">
              <TextField
                placeholder="Enter URL to submit"
                className="textField"
                value={textFieldValueUrlSubmit}
                validateOnLoad={false}
                onGetErrorMessage={() => {
                  return !SubmitUrlRegex.test(textFieldValueUrlSubmit)
                    ? StringConstants.UrlSubmitErrorMessage
                    : "";
                }}
                onChange={(event, val) => {
                  setTextFieldValueUrlSubmit(val?.trim() || "");
                }}
              />
            </div>
            <div className="modalFooter">
              <PrimaryButton
                className="button primaryButton"
                text="Submit URL"
                disabled={!SubmitUrlRegex.test(textFieldValueUrlSubmit)}
                onClick={onClickModalSubmitUrl}
              />
              <DefaultButton
                className="button secondaryButton"
                text="Cancel"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
          </div>
        )}
      </div>
    </>
  );
};
