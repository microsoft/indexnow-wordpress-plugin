import { IHttpResponse, useFetch, useSubmit } from "./APIHelper";
import {
  ISetApiKeyRequest,
  IGetApiSettingsResponse,
  IGetStatsResponse,
  IGetAllSubmissionsResponse,
  ISubmitUrlResponse,
  ISubmitUrlRequest,
  IGetApiKeyResponse,
  ISetApiKeyResponse,
  IRetryFailedSubmissionsResponse,
  IRetryFailedSubmissionsRequest,
  ISetAutoSubmissionEnabledResponse,
  ISetAutoSubmissionEnabledRequest,
  ICheckApiKeyValidityResponse,
  UrlSubmission,
} from "./Interfaces";

export async function GetApiKey() {
  let response: IHttpResponse<IGetApiKeyResponse>;
  const url = `apiKey`;
  response = await useFetch<IGetApiKeyResponse>(url).catch((err) => {
    console.error("Error while fetching API key.");
    return err;
  });
  return response;
}

export async function SetApiKey(apiKey: string) {
  let response: IHttpResponse<ISetApiKeyResponse>;
  const url = `apiKey`;
  const apiContent: ISetApiKeyRequest = {
    APIKey: apiKey,
  };
  response = await useSubmit<ISetApiKeyResponse>(url, apiContent).catch(
    (err) => {
      console.error("Error while updating API key.");
      return err;
    }
  );
  return response;
}

export async function CheckApiKeyValidity() {
  let response: IHttpResponse<ICheckApiKeyValidityResponse>;
  const url = `apiKeyValidity`;
  response = await useFetch<ICheckApiKeyValidityResponse>(url).catch((err) => {
    console.error("Error while checking API key validity.");
    return err;
  });
  return response;
}

export async function GetApiSettings() {
  let response: IHttpResponse<IGetApiSettingsResponse>;
  const url = `apiSettings`;
  response = await useFetch<IGetApiSettingsResponse>(url).catch((err) => {
    console.error("Error while fetching plugin settings.");
    return err;
  });
  return response;
}

export async function GetStats() {
  let response: IHttpResponse<IGetStatsResponse>;
  const url = `getStats`;
  response = await useFetch<IGetStatsResponse>(url).catch((err) => {
    console.error("Error while fetching submission statistics.");
    return err;
  });
  return response;
}

export async function GetAllSubmissions() {
  let response: IHttpResponse<IGetAllSubmissionsResponse>;
  const url = `allSubmissions`;
  response = await useFetch<IGetAllSubmissionsResponse>(url).catch((err) => {
    console.error("Error while fetching submitted URLs list.");
    return err;
  });
  return response;
}

export async function RetryFailedSubmissions(
  failedSubmissions: UrlSubmission[]
) {
  const param: IRetryFailedSubmissionsRequest = {
    Submissions: failedSubmissions,
  };
  let response: IHttpResponse<IRetryFailedSubmissionsResponse>;
  const url = `allSubmissions`;
  response = await useSubmit<IRetryFailedSubmissionsResponse>(url, param).catch(
    (err) => {
      console.error("Error while retrying failed submissions.");
      return err;
    }
  );
  return response;
}

export async function UpdateAutoSubmissionsEnabled(isEnabled: boolean) {
  const param: ISetAutoSubmissionEnabledRequest = {
    AutoSubmissionEnabled: isEnabled,
  };
  let response: IHttpResponse<ISetAutoSubmissionEnabledResponse>;
  const url = `automaticSubmission`;
  response = await useSubmit<ISetAutoSubmissionEnabledResponse>(
    url,
    param
  ).catch((err) => {
    console.error("Error while updating automatic submission settings.");
    return err;
  });
  return response;
}

export async function SubmitUrl(url: string) {
  let response: IHttpResponse<ISubmitUrlResponse>;
  const ep = `submitUrl`;
  const apiContent: ISubmitUrlRequest = {
    url: url,
  };
  response = await useSubmit<ISubmitUrlResponse>(ep, apiContent).catch(
    (err) => {
      console.error("Error while submitting URL.");
      return err;
    }
  );
  return response;
}
