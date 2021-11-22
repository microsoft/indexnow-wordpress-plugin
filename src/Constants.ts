export class StringConstants {
  static readonly ApiKeyHelpLink =
    "https://docs.microsoft.com/en-us/bingwebmaster/getting-access#using-api-key";
  static readonly BingWebmasterLink = "https://bing.com/webmaster";
  static readonly PluginInfoLink = "https://aka.ms/BingWordpressPluginAbout";
  static readonly ApiKeyValidationError =
    "Invalid API key! (Should be alphanumeric and 32 characters in length.)";
  // static readonly domain = "example.com";
  static readonly UrlSubmitErrorMessage = "Invalid URL!";
}

export const ApiKeyRegex = RegExp("^[a-zA-Z0-9]{0,32}$");
// eslint-disable-next-line no-useless-escape
// export const urlRegex = RegExp(`^(?:http(s)?:\/\/)?[\w.-]+(?:\.(${url})+)+[/\w.\S]*$`);

// eslint-disable-next-line no-useless-escape
export const SubmitUrlRegex = RegExp(
  `^https?:\\/\\/(www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b([-a-zA-Z0-9()@:%_\\+.~#?&//=]*)$`
);
