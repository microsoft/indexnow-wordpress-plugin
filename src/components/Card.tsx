import React from "react";
import { Tooltip } from "@fluentui/react-components";
import { Info24Regular } from "@fluentui/react-icons";

export interface ICardProps {
  title: string;
  tooltip: string;
  leadingIcon: React.ComponentType<{ className?: string }>;
  className?: string;
}

export const Card: React.FunctionComponent<React.PropsWithChildren<ICardProps>> = (props) => {
  const LeadingIcon = props.leadingIcon;

  return (
    <div className={"indexnow-Card " + (props.className || "")}>
      <div className="cardHeader">
        <span className="cardTitle">
          <LeadingIcon className="cardTitleIcon" />
          <span>{props.title}</span>

          <Tooltip
            content={props.tooltip}
            relationship="description"
            positioning="above"
          >
            <span tabIndex={0} style={{ display: "inline-flex", cursor: "help" }}>
              <Info24Regular className="info" />
            </span>
          </Tooltip>
        </span>
      </div>
      <div className="cardContent">
        {props.children}
      </div>
    </div>
  );
};
