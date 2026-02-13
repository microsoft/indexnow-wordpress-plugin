import React from "react";
import { Icon } from "@fluentui/react";
import { TooltipHost, ITooltipProps } from "@fluentui/react";
import { useId } from "@fluentui/react-hooks";

export interface ICardProps {
  title: string;
  tooltip: string;
  leadingIconName: string;
  className?: string;
}

export const Card: React.FunctionComponent<React.PropsWithChildren<ICardProps>> = (props) => {
  const tooltipId = useId(props.title);

  const tooltipProps: ITooltipProps = {
    onRenderContent: () => <span>{props.tooltip}</span>,
  };

  return (
    <div className={"indexnow-Card " + props.className || ""}>
      <div className="cardHeader">
        <span className="cardTitle">
          <Icon iconName={props.leadingIconName} className="cardTitleIcon" />
          <span>{props.title}</span>

          <TooltipHost
            closeDelay={500}
            directionalHint={1}
            id={tooltipId}
            tooltipProps={tooltipProps}
          >
            <Icon
              aria-describedby={tooltipId}
              iconName="Info"
              className="info"
            />
          </TooltipHost>
        </span>
      </div>
      <div className="cardContent">
        {props.children}
      </div>
    </div>
  );
};
