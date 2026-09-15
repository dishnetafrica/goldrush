import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';

class TitleHeading4Widget extends StatelessWidget {
  const TitleHeading4Widget({
    super.key,
    required this.text,
    this.textAlign,
    this.textOverflow,
    this.padding = paddingValue,
    this.opacity = 1.0,
    this.maxLines,
    this.fontSize,
    this.fontWeight,
    this.color = CustomColor.primaryLightTextColor,
  });

  final String text;
  final TextAlign? textAlign;
  final TextOverflow? textOverflow;
  final EdgeInsetsGeometry padding;
  final double opacity;
  final int? maxLines;
  final double? fontSize;
  final FontWeight? fontWeight;
  final Color? color;
  static const paddingValue = EdgeInsets.all(0.0);

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: opacity,
      child: Padding(
        padding: padding,
        child: Text(
          DynamicLanguage.isLoading ? "" : DynamicLanguage.key(text),
          style: Theme.of(context).brightness == Brightness.dark
              ? CustomStyle.darkHeading4TextStyle.copyWith(
                  fontSize: fontSize, fontWeight: fontWeight, color: color)
              : CustomStyle.lightHeading4TextStyle.copyWith(
                  fontSize: fontSize, fontWeight: fontWeight, color: color),
          textAlign: textAlign,
          overflow: textOverflow,
          maxLines: maxLines,
        ),
      ),
    );
  }
}
