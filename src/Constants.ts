export class StringConstants {
  static readonly IndexNowLink = "https://www.indexnow.org/";
  static readonly PluginInfoLink = "https://wordpress.org/plugins/indexnow/";
  static readonly ApiKeyValidationError =
    "Invalid API key! (Should be alphanumeric and 32 characters in length.)";
  static readonly UrlSubmitErrorMessage = "Invalid URL!";
}

export const ApiKeyRegex = RegExp("^[a-zA-Z0-9]{0,32}$");

export const SubmitUrlRegex = RegExp(
  `^https?:\\/\\/(www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b([-a-zA-Z0-9()@:%_\\+.~#?&//=]*)$`
);
