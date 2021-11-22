import { ErrorConstants } from "./ErrorConstants";

declare global {
    interface Window { wpr_object: any; }
}

const baseURL = `${window['wpr_object']['api_url']}`;

export interface IHttpResponse<T> extends Response {
  data?: T;
  error?: IAPIError;
}

export interface IAPIError {
  code: string;
  message: string;
  details?: IAPIErrorDetails[];
}

export interface IAPIErrorDetails {
  target: string;
  message: string;
}

const nonceHeader = {
  headers: {
    "X-WP-Nonce": `${window['wpr_object']['api_nonce']}`
  }
};

export async function useFetch<T>(
  requestUrl: RequestInfo
): Promise<IHttpResponse<T>> {
  return withTimeout<T>(
    new Promise((resolve, reject) => {
      let response: IHttpResponse<T>;
      fetch(`${baseURL}${requestUrl}`, nonceHeader)
        .then(res => {
          response = res;
          return res.json();
        })
        .then(body => {
          if (response.ok) {
            response.data = body;
          } else {
            response.error = body;
          }
          resolve(response);
        })
        .catch(err => {
          reject(err);
        });
    })
  );
}

export async function useSubmit<T>(
  requestUrl: RequestInfo,
  requestData: any
): Promise<IHttpResponse<T>> {
  return withTimeout<T>(
    new Promise((resolve, reject) => {
      let response: IHttpResponse<T>;
      fetch(`${baseURL}${requestUrl}`, {
        method: "POST",
        headers: {
          ...(nonceHeader.headers),
          "Accept": "application/json, text/javascript, */*; q=0.01",
          "Content-Type": "application/json;charset=UTF-8"
        },
        body: JSON.stringify(requestData)
      })
        .then(res => {
          response = res;
          return res.json();
        })
        .then(body => {
          if (response.ok) {
            response.data = body;
          } else {
            response.error = body;
          }
          resolve(response);
        })
        .catch(err => {
          reject(err);
        });
    })
  );
}

export function withTimeout<T>(
  promise: Promise<IHttpResponse<T>>,
  timeout = 60000
) {
  let timer: ReturnType<typeof setTimeout>;
  return Promise.race<IHttpResponse<T>>([
    promise,
    new Promise<IHttpResponse<T>>((resolve, _) => {
      let response: IHttpResponse<T>;
      const timeoutInit: ResponseInit = {
        status: ErrorConstants.RequestTimedOut.HttpStatusCode
      };
      response = new Response(null, timeoutInit);
      timer = setTimeout(() => {
        response.error = {
          code: ErrorConstants.RequestTimedOut.Code,
          message: ErrorConstants.RequestTimedOut.Message
        };
        resolve(response);
      }, timeout);
    })
  ]).then(result => {
    clearTimeout(timer);
    return result;
  });
}

export async function useDownload(
  requestUrl: RequestInfo,
  requestData: any,
  callback?: () => void
): Promise<IHttpResponse<void>> {
  return new Promise((resolve, reject) => {
    let response: any;
    fetch(`${baseURL}${requestUrl}`, {
      method: "POST",
      headers: {
        ...(nonceHeader.headers),
        "Accept": "application/json, text/javascript, */*; q=0.01",
        "Content-Type": "application/json;charset=UTF-8"
      },
      body: JSON.stringify(requestData)
    })
      .then(res => {
        response = res;
        return res.blob();
      })
      .then(blob => {
        const url: string = window.URL.createObjectURL(blob);
        const a: HTMLAnchorElement = document.createElement("a");
        a.href = url;
        a.download = response.headers
          .get("content-disposition")
          .split("filename=")[1];
        document.body.appendChild(a); // we need to append the element to the dom -> otherwise it will not work in firefox
        a.click();
        resolve(response);
        setTimeout(() => {
          a.remove(); // afterwards we remove the element again
        }, 100);
        if (callback) {
          callback();
        }
      })
      .catch(err => {
        reject(err);
      });
  });
}
