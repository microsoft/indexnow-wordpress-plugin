import "../scss/Dashboard.scss";

import * as React from "react";
import { useState, useEffect, useRef, useCallback } from "react";
import {
  Button,
  Input,
  Field,
  RadioGroup,
  Radio,
  Table,
  TableHeader,
  TableRow,
  TableHeaderCell,
  TableBody,
  TableCell,
  Skeleton,
  SkeletonItem,
} from "@fluentui/react-components";
import {
  Send24Regular,
  Rocket24Regular,
  TargetArrow24Regular,
  ErrorCircle24Filled,
  MoreVertical24Regular,
  ArrowSync24Regular,
  Dismiss24Regular,
  Checkmark24Regular,
  Edit24Regular,
  Delete24Regular,
  ShieldDismiss24Regular,
} from "@fluentui/react-icons";
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

  // -- Accessibility: modal refs and helpers --
  const modalRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLElement | null>(null);

  const openModal = useCallback((state: DashboardModalState) => {
    triggerRef.current = document.activeElement as HTMLElement;
    setModalState(state);
  }, []);

  const closeModal = useCallback(() => {
    setModalState(DashboardModalState.Hidden);
    setTimeout(() => triggerRef.current?.focus(), 0);
  }, []);

  // Focus trap + Escape key for modals (lightweight, no extra deps)
  useEffect(() => {
    if (modalState === DashboardModalState.Hidden) return;
    const modalEl = modalRef.current;
    if (!modalEl) return;

    const focusableSelector =
      'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    const getFocusable = () => modalEl.querySelectorAll<HTMLElement>(focusableSelector);

    // Auto-focus first focusable element
    requestAnimationFrame(() => {
      const els = getFocusable();
      if (els.length) els[0].focus();
    });

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        closeModal();
        return;
      }
      if (e.key === 'Tab') {
        const focusable = getFocusable();
        if (focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [modalState, closeModal]);

  // Close popover on outside click
  useEffect(() => {
    if (!showAutoSubmissionsPopOverMenu) return;
    const close = () => setShowAutoSubmissionsPopOverMenu(false);
    document.addEventListener('click', close);
    return () => document.removeEventListener('click', close);
  }, [showAutoSubmissionsPopOverMenu]);

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
  const formatSubmissionDate = (timestamp: number): string => {
    let time: Date = new Date(0);
    time.setUTCSeconds(timestamp);
    return time.getFullYear() === new Date().getFullYear()
      ? format(time, "d MMM 'at' HH':'mm", {})
      : format(time, "d MMM yyyy 'at' HH':'mm", {});
  };

  // Function handler for URL submission retries
  const resubmitItem = (submissionItem: UrlSubmission) => {
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
    closeModal();
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
    closeModal();
  };

  const onClickModalSubmitUrl = (
    event: React.MouseEvent<HTMLElement, MouseEvent>
  ) => {
    // hide modal and submit Url
    closeModal();
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
                <Card tooltip="This feature allows you to view Indexing insights of your site in Bing webmaster tools" leadingIcon={Send24Regular} title="IndexNow Insights">
                    <p className="cardDescription">
                        Monitor indexing status and performance of URLs submitted via IndexNow in Bing Webmaster tools.
                    </p>
                    <Button 
                        onClick={viewInsightsOnClick} 
                        style={{marginLeft: "26px"}}
                        className="button submitButton"
                    >
                        View Insights
                    </Button>
                </Card>
             </div>
             <div className="indexnow-CardColumn indexnow-CardColumn-2">
                <Card
                  title="Excluded Paths"
                  className={apiKeyInvalid ? "indexnow-Disabled" : ""}
                  tooltip="Configure URL paths that should be excluded from automatic IndexNow submissions"
                  leadingIcon={ShieldDismiss24Regular}
                >
                  <p className="cardDescription">
                    {excludedPathsList.length > 0
                      ? `${excludedPathsList.length} path pattern(s) excluded from auto-submission`
                      : "No paths excluded from auto-submission"}
                  </p>
                  <Button
                    disabled={apiKeyInvalid}
                    onClick={() => {
                      setEditingPathIndex(null);
                      setNewPathValue("");
                      openModal(DashboardModalState.EditExcludedPathsModal);
                    }}
                    style={{marginLeft: "26px"}}
                    className="button submitButton"
                  >
                    Manage Paths
                  </Button>
                </Card>
             </div>
        </div>
        <div className="indexnow-CardRow">
             <div className="indexnow-CardColumn indexnow-CardColumn-2">
            <Card
              title="Manual URL submission"
              tooltip="This feature allows you to submit a URL directly to IndexNow supporting search engines."
              leadingIcon={Send24Regular}
              className={apiKeyInvalid ? "indexnow-Disabled" : ""}
            >
              <p className="cardDescription">
                This feature allows you to submit a URL directly to IndexNow supporting search engines.
              </p>
              <Button
                disabled={apiKeyInvalid}
                onClick={() => {
                  // reset UI controls and display modal
                  setTextFieldValueUrlSubmit("");
                  openModal(DashboardModalState.SubmitUrlModal);
                }}
                style={{marginLeft : "26px"}}
                className="button submitButton"
              >
                Submit URL
              </Button>
            </Card>
          </div>

          <div className="indexnow-CardColumn indexnow-CardColumn-2">
            <Card
              title="Automate URL submission"
              className={
                "indexnow-Card-WithPopOver " + (apiKeyInvalid ? "indexnow-Disabled" : "")
              }
              tooltip="This feature allows to configure automation to submit new, updated & deleted URLs to IndexNow and stay updated."
              leadingIcon={Rocket24Regular}
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
              onClick={(e) => {
                e.stopPropagation();
                if (!apiKeyInvalid) {
                  setShowAutoSubmissionsPopOverMenu(!showAutoSubmissionsPopOverMenu);
                }
              }}
              onKeyDown={(e) => {
                if (e.key === 'Escape') setShowAutoSubmissionsPopOverMenu(false);
              }}
              role="button"
              tabIndex={0}
              aria-expanded={showAutoSubmissionsPopOverMenu}
              aria-haspopup="menu"
              aria-label="Auto-submission options"
            >
              <MoreVertical24Regular className="moreIcon" />
              <div className="popOverContainer">
                <ul
                  className={
                    "popOverPanel" +
                    (showAutoSubmissionsPopOverMenu ? " openPopOverMenu" : "")
                  }
                  role="menu"
                >
                  <li
                    role="menuitem"
                    tabIndex={showAutoSubmissionsPopOverMenu ? 0 : -1}
                    onClick={() => {
                      // reset UI controls settings and display modal
                      setSelectedOptionAutoSubmissions(
                        apiSettings?.AutoSubmissionEnabled
                          ? "enable"
                          : "disable"
                      );
                      openModal(
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
              leadingIcon={TargetArrow24Regular}
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
              leadingIcon={ErrorCircle24Filled}
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
          <Button
            disabled={
              submissionsList?.Submissions === null ||
              submissionsList?.Submissions.length === 0
            }
            onClick={downloadUrls}
            className="button submitButton"
          >
            Download
          </Button>
        </div>
        <div className="indexnow-CardRow">
          <div className="indexnow-CardColumn indexnow-CardColumn-1 indexnow-UrlSubmissions">
            <Table aria-label="URL submissions" className="indexnow-SubmissionsTable">
              <TableHeader>
                <TableRow>
                  <TableHeaderCell className="col-url">URL</TableHeaderCell>
                  <TableHeaderCell className="col-date">Submitted On</TableHeaderCell>
                  <TableHeaderCell className="col-status">Status</TableHeaderCell>
                  <TableHeaderCell className="col-action"></TableHeaderCell>
                </TableRow>
              </TableHeader>
              <TableBody>
                {submissionsList === undefined ? (
                  Array.from({length: 5}).map((_, i) => (
                    <TableRow key={i}>
                      <TableCell className="col-url"><Skeleton><SkeletonItem /></Skeleton></TableCell>
                      <TableCell className="col-date"><Skeleton><SkeletonItem /></Skeleton></TableCell>
                      <TableCell className="col-status"><Skeleton><SkeletonItem /></Skeleton></TableCell>
                      <TableCell className="col-action"><Skeleton><SkeletonItem /></Skeleton></TableCell>
                    </TableRow>
                  ))
                ) : (
                  (submissionsList?.Submissions ?? []).map((item, index) => (
                    <TableRow key={index}>
                      <TableCell className="col-url">
                        <a href={item.url} target="_blank" rel="noreferrer">
                          {decodeURI(item.url)}
                        </a>
                      </TableCell>
                      <TableCell className="col-date">{formatSubmissionDate(item.submission_date)}</TableCell>
                      <TableCell className="col-status">{item.error === "Success" ? item.error : `Failed - ${item.error}`}</TableCell>
                      <TableCell className="col-action">
                        <Button
                          appearance="transparent"
                          icon={<ArrowSync24Regular />}
                          onClick={() => resubmitItem(item)}
                          aria-label="Resubmit"
                          size="small"
                        />
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
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
      {modalState !== DashboardModalState.Hidden && (
        <div
          className="indexnow-ModalBackdrop"
          onClick={closeModal}
          aria-hidden="true"
        />
      )}
      <div
        ref={modalRef}
        className={
          "indexnow-Modal" +
          (modalState !== DashboardModalState.Hidden ? " showModal" : "")
        }
        role={modalState !== DashboardModalState.Hidden ? "dialog" : undefined}
        aria-modal={modalState !== DashboardModalState.Hidden ? "true" : undefined}
        aria-labelledby={modalState !== DashboardModalState.Hidden ? "indexnow-modal-title" : undefined}
      >
        {modalState === DashboardModalState.UpdateApiKeyModal && (
          <div className={"modalContainer indexnow-ModalUpdateApiKey"}>
            <div className="modalHeader">
              <p className="modalTitle" id="indexnow-modal-title">API Key</p>
              <Button
                appearance="transparent"
                icon={<Dismiss24Regular />}
                className="modalClose"
                onClick={closeModal}
                aria-label="Close"
                size="small"
              />
            </div>
            <div className="modalContent">
              <Input
                readOnly
                placeholder="Enter 32 digit API key"
                className="textField"
                value={textFieldValueApiKey}
                onChange={(event, data) => {
                  setTextFieldValueApiKey(data.value);
                }}
              />
            </div>
            <div className="modalFooter">
              <Button
                className="button secondaryButton"
                onClick={closeModal}
              >
                Got it
              </Button>
            </div>
          </div>
        )}
        {modalState === DashboardModalState.EditPrefAutoSubmissionModal && (
          <div className="modalContainer indexnow-ModalEditPreferenceAutoSubmissions">
            <div className="modalHeader">
              <p className="modalTitle" id="indexnow-modal-title">
                Edit preference for Automate URL Submission
              </p>
              <Button
                appearance="transparent"
                icon={<Dismiss24Regular />}
                className="modalClose"
                onClick={closeModal}
                aria-label="Close"
                size="small"
              />
            </div>
            <div className="modalContent">
              <p className="modalDescription">
                We recommend you to enable automation to submit new, updated &
                deleted URLs to IndexNow and stay updated.
              </p>
              <RadioGroup
                value={selectedOptionAutoSubmissions}
                onChange={(event, data) => {
                  setSelectedOptionAutoSubmissions(data.value);
                }}
              >
                <Radio value="enable" label="Enable (recommended)" />
                <Radio value="disable" label="Disable" />
              </RadioGroup>
            </div>
            <div className="modalFooter">
              <Button
                appearance="primary"
                className="button primaryButton"
                onClick={onClickUpdateAutoSubmissions}
                disabled={
                  (apiSettings?.AutoSubmissionEnabled
                    ? "enable"
                    : "disable") === selectedOptionAutoSubmissions
                }
              >
                Save
              </Button>
              <Button
                className="button secondaryButton"
                onClick={closeModal}
              >
                Cancel
              </Button>
            </div>
          </div>
        )}

        {modalState === DashboardModalState.SubmitUrlModal && (
          <div className="modalContainer indexnow-ModalUrlSubmit">
            <div className="modalHeader">
              <p className="modalTitle" id="indexnow-modal-title">Manual URL submission</p>
              <Button
                appearance="transparent"
                icon={<Dismiss24Regular />}
                className="modalClose"
                onClick={closeModal}
                aria-label="Close"
                size="small"
              />
            </div>
            <div className="modalContent">
              <Field
                validationMessage={
                  textFieldValueUrlSubmit.length > 0 && !SubmitUrlRegex.test(textFieldValueUrlSubmit)
                    ? StringConstants.UrlSubmitErrorMessage
                    : undefined
                }
                validationState={
                  textFieldValueUrlSubmit.length > 0 && !SubmitUrlRegex.test(textFieldValueUrlSubmit)
                    ? "error"
                    : "none"
                }
              >
                <Input
                  placeholder="Enter URL to submit"
                  className="textField"
                  value={textFieldValueUrlSubmit}
                  onChange={(event, data) => {
                    setTextFieldValueUrlSubmit(data.value?.trim() || "");
                  }}
                />
              </Field>
            </div>
            <div className="modalFooter">
              <Button
                appearance="primary"
                className="button primaryButton"
                disabled={!SubmitUrlRegex.test(textFieldValueUrlSubmit)}
                onClick={onClickModalSubmitUrl}
              >
                Submit URL
              </Button>
              <Button
                className="button secondaryButton"
                onClick={closeModal}
              >
                Cancel
              </Button>
            </div>
          </div>
        )}

        {modalState === DashboardModalState.EditExcludedPathsModal && (
          <div className="modalContainer indexnow-ModalExcludedPaths">
            <div className="modalHeader">
              <p className="modalTitle" id="indexnow-modal-title">Manage Excluded Paths</p>
              <Button
                appearance="transparent"
                icon={<Dismiss24Regular />}
                className="modalClose"
                onClick={() => {
                  closeModal();
                  setEditingPathIndex(null);
                  setNewPathValue("");
                }}
                aria-label="Close"
                size="small"
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
                <Input
                  placeholder="Enter path pattern (e.g., /private/*)"
                  style={{flex: 1}}
                  value={newPathValue}
                  disabled={excludedPathsList.length >= MAX_EXCLUDED_PATHS}
                  onChange={(event, data) => setNewPathValue(data.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      addExcludedPath();
                    }
                  }}
                />
                <Button
                  appearance="primary"
                  disabled={!newPathValue.trim() || excludedPathsList.length >= MAX_EXCLUDED_PATHS}
                  onClick={addExcludedPath}
                >
                  Add
                </Button>
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
                          <Input
                            value={editingPathValue}
                            style={{flex: 1, marginRight: "10px"}}
                            onChange={(event, data) => setEditingPathValue(data.value)}
                            onKeyDown={(e) => {
                              if (e.key === 'Enter') {
                                saveEditedPath();
                              }
                            }}
                          />
                          <Button
                            appearance="transparent"
                            icon={<Checkmark24Regular />}
                            aria-label="Save"
                            onClick={saveEditedPath}
                            style={{color: "#107c10"}}
                            size="small"
                          />
                          <Button
                            appearance="transparent"
                            icon={<Dismiss24Regular />}
                            aria-label="Cancel"
                            onClick={cancelEditingPath}
                            size="small"
                          />
                        </>
                      ) : (
                        <>
                          <code style={{flex: 1, fontSize: "13px"}}>{path}</code>
                          <Button
                            appearance="transparent"
                            icon={<Edit24Regular />}
                            aria-label="Edit path"
                            onClick={() => startEditingPath(index)}
                            size="small"
                          />
                          <Button
                            appearance="transparent"
                            icon={<Delete24Regular />}
                            aria-label="Delete path"
                            onClick={() => deleteExcludedPath(index)}
                            style={{color: "#a80000"}}
                            size="small"
                          />
                        </>
                      )}
                    </div>
                  ))
                )}
              </div>
            </div>
            <div className="modalFooter">
              <Button
                className="button secondaryButton"
                onClick={() => {
                  closeModal();
                  setEditingPathIndex(null);
                  setNewPathValue("");
                }}
              >
                Close
              </Button>
            </div>
          </div>
        )}
      </div>
    </>
  );
};
