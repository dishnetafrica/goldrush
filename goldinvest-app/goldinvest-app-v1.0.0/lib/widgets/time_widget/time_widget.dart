import 'package:flutter/material.dart';

import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';

class DateInfoWidget extends StatelessWidget {
  final String? dateText;
  final String? monthText;
  final double? borderRadius;
  final double? horizontalPadding;
  final double? verticalPadding;
  final Color? backgroundColor;
  final TextStyle? dateTextStyle;
  final TextStyle? monthTextStyle;

  const DateInfoWidget({
    super.key,
    this.dateText,
    this.monthText,
    this.borderRadius,
    this.horizontalPadding,
    this.verticalPadding,
    this.backgroundColor,
    this.dateTextStyle,
    this.monthTextStyle,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: backgroundColor ?? Colors.black.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(borderRadius ?? Dimensions.radius),
      ),
      padding: EdgeInsets.symmetric(
        horizontal: horizontalPadding ?? Dimensions.widthSize * 0.7,
        vertical: verticalPadding ?? Dimensions.widthSize * 0.7,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (dateText != null)
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                dateText!,
                style: dateTextStyle ??
                    TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: Dimensions.headingTextSize1 * 0.7,
                      color: CustomColor.primaryLightTextColor,
                      height: 1.0,
                    ),
              ),
            ),
          if (monthText != null)
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(
                monthText!,
                style: monthTextStyle ??
                    TextStyle(
                      fontWeight: FontWeight.w500,
                      fontSize: Dimensions.headingTextSize2 * 0.5,
                      height: 1.0,
                    ),
              ),
            ),
        ],
      ),
    );
  }
}
