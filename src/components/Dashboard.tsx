import "../scss/Dashboard.scss";

import * as React from "react";
import { useState, useEffect } from "react";
import { DefaultButton, PrimaryButton } from "@fluentui/react";
import { Icon } from "@fluentui/react";
import {
  GetApiSettings,
  GetStats,
  GetAllSubmissions,
  RetryFailedSubmissions,
  SubmitUrl,
  UpdateAutoSubmissionsEnabled,
  GetApiKey,
  GetIndexNowInsightsUrl,
  UpdateExcludedPaths
} from "./withDashboardData";
import { ShimmeredDetailsList } from "@fluentui/react";
import {
  IColumn,
  SelectionMode,
  IChoiceGroupOption,
  ChoiceGroup,
  TextField,
  IconButton,
} from "@fluentui/react";
import { format, formatISO } from "date-fns";
import {
  IGetStatsResponse,
  IGetApiSettingsResponse,
  IGetAllSubmissionsResponse,
  UrlSubmission,
  IGetInsightsUrlResponse,
} from "./Interfaces";
import { Card } from "./Card";
import { StringConstants, ApiKeyRegex, SubmitUrlRegex } from "../Constants";
import { IHttpResponse } from "./IndexNowAPIHelper";

interface IDashboardProps {
  addBanner: (str: string) => void;
}

export const Dashboard: React.FunctionComponent<IDashboardProps> = (props) => {
  enum DashboardModalState {
    Hidden = 0,
    UpdateApiKeyModal = 1,
    EditPrefAutoSubmissionModal = 2,
    SubmitUrlModal = 3,
    EditExcludedPathsModal = 4,
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
  const [textFieldValueExcludedPaths, setTextFieldValueExcludedPaths] = useState<string>("");
  
  // Excluded paths management state
  const [excludedPathsList, setExcludedPathsList] = useState<string[]>([]);
  const [newPathValue, setNewPathValue] = useState<string>("");
  const [editingPathIndex, setEditingPathIndex] = useState<number | null>(null);
  const [editingPathValue, setEditingPathValue] = useState<string>("");
  const MAX_EXCLUDED_PATHS = 20;

  useEffect(() => {
    const data = Promise.resolve(GetApiKey());
    data.then((response) => {
      if (response && response.data) {
        setTextFieldValueApiKey(String (response.data.APIKey));
      }
    });
  }, []);
  // variables to trigger UI data refresh
  const [urlSubmitted, setUrlSubmitted] = useState<number>(0);
  const [apiSettingsUpdated, setApiSettingsUpdated] = useState<number>(0);
  const [apiKeyUpdated, setApiKeyUpdated] = useState<number>(0);

  // Get API settings
  useEffect(() => {
    Promise.resolve(GetApiSettings()).then((response) => {
      if (response && response.data && response.data.error_type.length === 0) {
        setAPISettings(response.data);
        setSelectedOptionAutoSubmissions(
          response.data.AutoSubmissionEnabled ? "enable" : "disable"
        );
        setTextFieldValueExcludedPaths(response.data.ExcludedPaths || "");
        // Parse excluded paths into array
        const paths = (response.data.ExcludedPaths || "")
          .split('\n')
          .map((p: string) => p.trim())
          .filter((p: string) => p.length > 0);
        setExcludedPathsList(paths);
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
          <a href={item.url} target="_blank" rel="noreferrer">
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
            className="indexnow-Icon retryIcon"
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

  // Function handler for fetching URL for view Indexing Insights to Bing webmaster tools.
  const viewInsightsOnClick = (
    event: React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    Promise.resolve(GetIndexNowInsightsUrl()).then(
      (response : IHttpResponse<IGetInsightsUrlResponse>) => {
        if (response && response.data) {
          window.open(response.data?.InsightsUrl, "_blank")
        }
      }
    );
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

  // Save excluded paths list to server
  const saveExcludedPaths = (paths: string[]) => {
    const pathsString = paths.join('\n');
    Promise.resolve(UpdateExcludedPaths(pathsString)).then((response) => {
      if (response && response.data) {
        setApiSettingsUpdated(apiSettingsUpdated + 1);
        if (response.data.error_type.length === 0) {
          props.addBanner("Success : Excluded paths updated successfully.");
        } else {
          props.addBanner("Error : Excluded paths could not be updated.");
        }
      }
    });
  };

  // Add a new excluded path
  const addExcludedPath = () => {
    const trimmedPath = newPathValue.trim();
    if (trimmedPath && !excludedPathsList.includes(trimmedPath)) {
      if (excludedPathsList.length >= MAX_EXCLUDED_PATHS) {
        props.addBanner(`Error : Maximum of ${MAX_EXCLUDED_PATHS} excluded paths allowed.`);
        return;
      }
      const newList = [...excludedPathsList, trimmedPath];
      setExcludedPathsList(newList);
      setNewPathValue("");
      saveExcludedPaths(newList);
    }
  };

  // Delete an excluded path
  const deleteExcludedPath = (index: number) => {
    const newList = excludedPathsList.filter((_, i) => i !== index);
    setExcludedPathsList(newList);
    saveExcludedPaths(newList);
  };

  // Start editing a path
  const startEditingPath = (index: number) => {
    setEditingPathIndex(index);
    setEditingPathValue(excludedPathsList[index]);
  };

  // Save edited path
  const saveEditedPath = () => {
    if (editingPathIndex !== null) {
      const trimmedPath = editingPathValue.trim();
      if (trimmedPath) {
        const newList = [...excludedPathsList];
        newList[editingPathIndex] = trimmedPath;
        setExcludedPathsList(newList);
        saveExcludedPaths(newList);
      }
      setEditingPathIndex(null);
      setEditingPathValue("");
    }
  };

  // Cancel editing
  const cancelEditingPath = () => {
    setEditingPathIndex(null);
    setEditingPathValue("");
  };

  const onClickUpdateExcludedPaths = (
    event: React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    setModalState(DashboardModalState.Hidden);
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
          "indexnow-DashboardContent" +
          (modalState !== DashboardModalState.Hidden ? " darken" : "")
        }
      >
        <div className="sectionTitleContainer">
            <h2 className="sectionTitle">IndexNow Insights in Bing Webmaster tools</h2>
        </div>
        <div className="indexnow-CardRow">
             <div className="indexnow-CardColumn indexnow-CardColumn-2">
                <Card tooltip="This feature allows you to view Indexing insights of your site in Bing webmaster tools" leadingIconName="Send" title="IndexNow Insights">
                    <p className="cardDescription">
                        Monitor indexing status and performance of URLs submitted via IndexNow in Bing Webmaster tools.
                    </p>
                    <DefaultButton 
                        onClick={viewInsightsOnClick} 
                        style={{marginLeft: "26px"}}
                        className="button submitButton" 
                        text="View Insights" 
                    />
                </Card>
             </div>
             <div className="indexnow-CardColumn indexnow-CardColumn-2">
                <Card
                  title="Excluded Paths"
                  className={apiKeyInvalid ? "indexnow-Disabled" : ""}
                  tooltip="Configure URL paths that should be excluded from automatic IndexNow submissions"
                  leadingIconName="BlockedSite"
                >
                  <p className="cardDescription">
                    {excludedPathsList.length > 0
                      ? `${excludedPathsList.length} path pattern(s) excluded from auto-submission`
                      : "No paths excluded from auto-submission"}
                  </p>
                  <DefaultButton
                    disabled={apiKeyInvalid}
                    onClick={() => {
                      setEditingPathIndex(null);
                      setNewPathValue("");
                      setModalState(DashboardModalState.EditExcludedPathsModal);
                    }}
                    style={{marginLeft: "26px"}}
                    className="button submitButton"
                    text="Manage Paths"
                  />
                </Card>
             </div>
        </div>
        <div className="indexnow-CardRow">
             <div className="indexnow-CardColumn indexnow-CardColumn-2">
            <Card
              title="Manual URL submission"
              tooltip="This feature allows you to submit a URL directly to IndexNow supporting search engines."
              leadingIconName="Send"
              className={apiKeyInvalid ? "indexnow-Disabled" : ""}
            >
              <p className="cardDescription">
                This feature allows you to submit a URL directly to IndexNow supporting search engines.
              </p>
              <DefaultButton
                disabled={apiKeyInvalid}
                onClick={() => {
                  // reset UI controls and display modal
                  setTextFieldValueUrlSubmit("");
                  setModalState(DashboardModalState.SubmitUrlModal);
                }}
                style={{marginLeft : "26px"}}
                className="button submitButton"
                text="Submit URL"
              />
            </Card>
          </div>

          <div className="indexnow-CardColumn indexnow-CardColumn-2">
            <Card
              title="Automate URL submission"
              className={
                "indexnow-Card-WithPopOver " + (apiKeyInvalid ? "indexnow-Disabled" : "")
              }
              tooltip="This feature allows to configure automation to submit new, updated & deleted URLs to IndexNow and stay updated."
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
                "indexnow-PopOverMenu " + (apiKeyInvalid ? "indexnow-Disabled" : "")
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

        <div className="indexnow-CardRow">
          <div className="indexnow-OverviewSection">
            <div className="indexnow-CardColumn indexnow-CardColumn-2">
            <Card
              title="Successful submissions"
              leadingIconName={"Bullseye"}
              tooltip={"Successful submissions "}
            >
              <p className="cardDescription">
              <h2>
                {submissionStats &&
                submissionStats.PassedSubmissionCount !== null
                  ? submissionStats.PassedSubmissionCount
                  : "-"}
              </h2>
              <p>In last 48 hours</p>
              </p>
            </Card>
            </div>
            <div className="indexnow-CardColumn indexnow-CardColumn-2">
            <Card
              title="Failed submissions"
              leadingIconName={"StatusErrorFull"}
              tooltip={"Failed submissions "}
            >
              <p className="cardDescription">
              <h2>
              {submissionStats &&
                submissionStats.FailedSubmissionCount !== null
                  ? submissionStats.FailedSubmissionCount
                  : "-"}
              </h2>
              <p>In last 48 hours</p>
              </p>
            </Card>
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
            className="button submitButton"
            text="Download"
          />
        </div>
        <div className="indexnow-CardRow">
          <div className="indexnow-CardColumn indexnow-CardColumn-1 indexnow-UrlSubmissions">
            <ShimmeredDetailsList
              setKey="items"
              items={submissionsList?.Submissions ?? []}
              columns={urlSubmissionTableColumns}
              selectionMode={SelectionMode.none}
              enableShimmer={submissionsList === undefined}
              detailsListStyles={{root : {borderRadius : "12px"}}}
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
            Learn more about {" "}
            <a href={StringConstants.IndexNowLink} target="_blank" rel="noreferrer">IndexNow!</a>
          </p>
        </div>
      </div>
      <div
        className={
          "indexnow-Modal" +
          (modalState !== DashboardModalState.Hidden ? " showModal" : "")
        }
      >
        {modalState === DashboardModalState.UpdateApiKeyModal && (
          <div className={"modalContainer indexnow-ModalUpdateApiKey"}>
            <div className="modalHeader">
              <p className="modalTitle">API Key</p>
              <Icon
                iconName="ChromeClose"
                className="indexnow-Icon modalClose"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
            <div className="modalContent">
              <TextField
              readOnly = {true}
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
            </div>
            <div className="modalFooter">
              <DefaultButton
                className="button secondaryButton"
                text="Got it"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
          </div>
        )}
        {modalState === DashboardModalState.EditPrefAutoSubmissionModal && (
          <div className="modalContainer indexnow-ModalEditPreferenceAutoSubmissions">
            <div className="modalHeader">
              <p className="modalTitle">
                Edit preference for Automate URL Submission
              </p>
              <Icon
                iconName="ChromeClose"
                className="indexnow-Icon modalClose"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                }}
              />
            </div>
            <div className="modalContent">
              <p className="modalDescription">
                We recommend you to enable automation to submit new, updated &
                deleted URLs to IndexNow and stay updated.
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
          <div className="modalContainer indexnow-ModalUrlSubmit">
            <div className="modalHeader">
              <p className="modalTitle">Manual URL submission</p>
              <Icon
                iconName="ChromeClose"
                className="indexnow-Icon modalClose"
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

        {modalState === DashboardModalState.EditExcludedPathsModal && (
          <div className="modalContainer indexnow-ModalExcludedPaths">
            <div className="modalHeader">
              <p className="modalTitle">Manage Excluded Paths</p>
              <Icon
                iconName="ChromeClose"
                className="indexnow-Icon modalClose"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                  setEditingPathIndex(null);
                  setNewPathValue("");
                }}
              />
            </div>
            <div className="modalContent">
              <p className="modalDescription">
                URL paths matching these patterns will be excluded from automatic IndexNow submissions.
              </p>
              <p className="modalDescription">
                Wildcards supported: <code>*</code> (any characters), <code>?</code> (single character).
              </p>
              <p className="modalDescription" style={{marginBottom: "15px", color: "#666"}}>
                Examples: <code>/private/*</code>, <code>/draft-*</code>, <code>/internal/page</code>.
              </p>

              {/* Add new path */}
              <div style={{display: "flex", gap: "10px", marginBottom: "15px"}}>
                <TextField
                  placeholder="Enter path pattern (e.g., /private/*)"
                  style={{flex: 1}}
                  value={newPathValue}
                  disabled={excludedPathsList.length >= MAX_EXCLUDED_PATHS}
                  onChange={(event, val) => setNewPathValue(val || "")}
                  onKeyPress={(e) => {
                    if (e.key === 'Enter') {
                      addExcludedPath();
                    }
                  }}
                />
                <PrimaryButton
                  text="Add"
                  disabled={!newPathValue.trim() || excludedPathsList.length >= MAX_EXCLUDED_PATHS}
                  onClick={addExcludedPath}
                />
              </div>

              <p style={{fontSize: "12px", color: "#666", marginBottom: "10px"}}>
                {excludedPathsList.length} / {MAX_EXCLUDED_PATHS} paths configured
              </p>

              {/* List of excluded paths */}
              <div style={{maxHeight: "300px", overflowY: "auto", border: "1px solid #edebe9", borderRadius: "4px"}}>
                {excludedPathsList.length === 0 ? (
                  <p style={{padding: "20px", textAlign: "center", color: "#666"}}>
                    No excluded paths configured. Add a path pattern above.
                  </p>
                ) : (
                  excludedPathsList.map((path, index) => (
                    <div 
                      key={index} 
                      style={{
                        display: "flex", 
                        alignItems: "center", 
                        padding: "8px 12px",
                        borderBottom: index < excludedPathsList.length - 1 ? "1px solid #edebe9" : "none",
                        backgroundColor: index % 2 === 0 ? "#faf9f8" : "#fff"
                      }}
                    >
                      {editingPathIndex === index ? (
                        <>
                          <TextField
                            value={editingPathValue}
                            style={{flex: 1, marginRight: "10px"}}
                            onChange={(event, val) => setEditingPathValue(val || "")}
                            onKeyPress={(e) => {
                              if (e.key === 'Enter') {
                                saveEditedPath();
                              }
                            }}
                          />
                          <IconButton
                            iconProps={{iconName: "CheckMark"}}
                            title="Save"
                            onClick={saveEditedPath}
                            style={{color: "#107c10"}}
                          />
                          <IconButton
                            iconProps={{iconName: "Cancel"}}
                            title="Cancel"
                            onClick={cancelEditingPath}
                          />
                        </>
                      ) : (
                        <>
                          <code style={{flex: 1, fontSize: "13px"}}>{path}</code>
                          <IconButton
                            iconProps={{iconName: "Edit"}}
                            title="Edit path"
                            onClick={() => startEditingPath(index)}
                          />
                          <IconButton
                            iconProps={{iconName: "Delete"}}
                            title="Delete path"
                            onClick={() => deleteExcludedPath(index)}
                            style={{color: "#a80000"}}
                          />
                        </>
                      )}
                    </div>
                  ))
                )}
              </div>
            </div>
            <div className="modalFooter">
              <DefaultButton
                className="button secondaryButton"
                text="Close"
                onClick={() => {
                  setModalState(DashboardModalState.Hidden);
                  setEditingPathIndex(null);
                  setNewPathValue("");
                }}
              />
            </div>
          </div>
        )}
      </div>
    </>
  );
};
