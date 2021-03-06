import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import { App } from './components/App';
import * as serviceWorker from './serviceWorker';
import { initializeIcons } from "@fluentui/react/lib/Icons";

initializeIcons();

var rootElement = document.getElementById("indexNowAppRoot");
if (rootElement !== null) {
  ReactDOM.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>,
    rootElement
  );
}

// If you want your app to work offline and load faster, you can change
// unregister() to register() below. Note this comes with some pitfalls.
// Learn more about service workers: https://bit.ly/CRA-PWA
serviceWorker.unregister();
