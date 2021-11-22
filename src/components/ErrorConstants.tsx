export const ErrorConstants = {
    NoDataFound: {
      Code: "NoDataFound",
      Message: "No data available"
    },

    RequestTimedOut: {
      HttpStatusCode: 408,
      Code: "RequestTimedOut",
      Message:
        "This might be a momentary issue, please try again or check back later"
    },

    UrlNotAllowed: {
      Code: "UrlNotAllowed",
      Message:
        "We found that the URL submitted for block is important for Bing users and hence cannot be blocked through Bing Webmaster tools."
    }
  };
