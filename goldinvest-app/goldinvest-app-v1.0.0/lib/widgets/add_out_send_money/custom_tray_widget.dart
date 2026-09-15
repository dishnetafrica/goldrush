import 'package:flutter/material.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading2_widget.dart';

class CustomIconWithLabelWidget extends StatelessWidget {
  final String imagePath;
  final String labelText;
  final double? iconPadding;
  final double? textPadding;
  final double? fontSize;
  final Color? backgroundColor;
  final Color? textColor;
  final VoidCallback onTap;

  const CustomIconWithLabelWidget({
    super.key,
    required this.imagePath,
    required this.labelText,
    this.iconPadding,
    this.textPadding,
    this.fontSize,
    this.backgroundColor,
    this.textColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        GestureDetector(
          onTap: onTap,
          child: Container(
            padding: EdgeInsets.symmetric(
              vertical: iconPadding ?? Dimensions.paddingSize,
              horizontal: iconPadding ?? Dimensions.paddingSize,
            ),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: backgroundColor ?? CustomColor.whiteColor,
              boxShadow: [
                BoxShadow(
                  color: CustomColor.blackColor.withValues(alpha: 0.1),
                  spreadRadius: 0,
                  blurRadius: 10,
                  offset: const Offset(0, 0),
                ),
              ],
            ),
            child: CustomImageWidget(
              path: imagePath,
            ),
          ),
        ),
        Padding(
          padding: EdgeInsets.symmetric(
            vertical: textPadding ?? Dimensions.paddingSize * 0.5,
          ),
          child: TitleHeading2Widget(
            text: labelText,
            fontSize: fontSize ?? Dimensions.headingTextSize3,
            color: textColor ?? CustomColor.primaryLightTextColor,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}
