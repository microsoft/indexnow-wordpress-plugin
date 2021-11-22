interface ApiResponse {
  errors?: any
}

export interface IGetApiKeyResponse extends ApiResponse {
  hasAPIKey: boolean;
}

export interface ISetApiKeyRequest {
  APIKey: string;
}

export interface ISetApiKeyResponse extends ApiResponse {
  error_type: string;
}

export interface ICheckApiKeyValidityResponse extends ApiResponse {
  error_type: string;
}

export interface IGetApiSettingsResponse extends ApiResponse {
  AutoSubmissionEnabled: boolean;
  SiteUrl: string;
  error_type: string;
}

export interface IGetStatsResponse extends ApiResponse {
  FailedSubmissionCount: number;
  PassedSubmissionCount: number;
  Quota: number;
  error_type: string;
}

export interface IGetAllSubmissionsResponse extends ApiResponse {
  Submissions: UrlSubmission[];
  error_type: string;
}

export interface IRetryFailedSubmissionsRequest {
  Submissions: UrlSubmission[];
}

export interface IRetryFailedSubmissionsResponse extends ApiResponse {
  hasError: boolean;
  SubmissionErrors: SubmissionErrors[];
  error_type: string;
}

export interface ISubmitUrlRequest {
  url: string;
}

export interface ISubmitUrlResponse extends ApiResponse {
  error: string;
}

export interface ISetAutoSubmissionEnabledRequest {
  AutoSubmissionEnabled: boolean;
}

export interface ISetAutoSubmissionEnabledResponse extends ApiResponse {
  error_type: string;
}

export interface UrlSubmission {
  url: string;
  submission_type: number;
  submission_date: number;
  error: string;
  type: SubmissionType;
}

enum SubmissionType {
  add,
  update,
  delete,
}

export interface SubmissionErrors {
  url: string;
  isSubmitted: boolean;
  status: string;
  error_msg: string;
}
