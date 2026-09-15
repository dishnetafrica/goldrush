import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../others/custom_image_widget.dart';

/// >>> Border Side Style
enum BSS {
  enabledBorder,
  b,
  disableBorder,
  focusedBorder,
  errorBorder,
  focusedErrorBorder
}

enum BorderStyle {
  outline,
  underline,
  none,
}

// ignore: must_be_immutable
class PrimaryInputWidget extends StatefulWidget {
  final String hintText, phoneCode;
  final String? prefixIconPath;
  final int maxLines;
  final bool isValidator;
  final bool isPasswordField;
  final bool autoFocus;
  final bool readOnly;
  final bool isFilled;
  final bool showBorderSide;
  final bool validator;
  final Widget? prefixIcon;
  final Widget? suffixIcon;
  final double? padding;
  final double? radius;
  final double borderWidth;
  final Color? fillColor;
  final Color? shadowColor;
  final Function(String)? onChanged;
  final Decoration? customShapeDecoration;
  final EdgeInsetsGeometry? customPadding;
  TextEditingController? textController;
  final TextInputType? textInputType;
  final List<TextInputFormatter>? inputFormatters;
  final AlignmentGeometry? alignment;
  final BorderStyle borderStyle;
  final double? height;

  PrimaryInputWidget({
    super.key,
    required this.hintText,
    this.prefixIconPath = "",
    this.phoneCode = "",
    this.isValidator = true,
    this.isPasswordField = false,
    this.isFilled = true,
    this.validator = false,
    this.autoFocus = false,
    this.readOnly = false,
    this.prefixIcon,
    this.suffixIcon,
    this.maxLines = 1,
    this.borderWidth = 2,
    this.radius = 12,
    this.customPadding,
    this.padding,
    this.textInputType,
    this.inputFormatters,
    this.alignment,
    this.shadowColor,
    this.borderStyle = BorderStyle.outline,
    this.fillColor,
    this.showBorderSide = false,
    this.customShapeDecoration,
    this.onChanged,
    this.textController, // Nullable TextEditingController
    this.height = 68,
  });

  @override
  State<PrimaryInputWidget> createState() => _PrimaryInputWidgetState();
}

class _PrimaryInputWidgetState extends State<PrimaryInputWidget> {
  FocusNode? focusNode;
  bool isVisibility = true;

  @override
  void initState() {
    super.initState();
    focusNode = FocusNode();
    widget.textController ??= TextEditingController();
  }

  @override
  void dispose() {
    focusNode?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return widget.alignment != null
        ? Align(
            alignment: widget.alignment ?? Alignment.center,
            child: _buildTextFormFieldWidget(context),
          )
        : _buildTextFormFieldWidget(context);
  }

  Widget _buildTextFormFieldWidget(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.start,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildTitle(context),
        SizedBox(
          height: widget.height,
          child: TextFormField(
            controller: widget.textController,
            readOnly: widget.readOnly,
            focusNode: focusNode,
            style: _setFontStyle(context),
            inputFormatters: widget.inputFormatters,
            obscureText: widget.isPasswordField ? isVisibility : false,
            textInputAction: TextInputAction.next,
            keyboardType: widget.textInputType,
            maxLines: widget.maxLines,
            decoration: _buildDecoration(context),
            validator: _setValidator(),
            cursorColor: CustomColor.blackColor,
            onChanged: widget.onChanged,
            onTap: () {
              if (!widget.readOnly) {
                setState(() {
                  focusNode!.requestFocus();
                });
              }
            },
            onFieldSubmitted: (value) {
              if (!widget.readOnly) {
                setState(() {
                  focusNode!.unfocus();
                });
              }
            },
            onEditingComplete: () {
              if (!widget.readOnly) {
                setState(() {
                  focusNode!.unfocus();
                });
              }
            },
            onTapOutside: (value) {
              if (!widget.readOnly) {
                setState(() {
                  focusNode!.unfocus();
                });
              }
            },
          ),
        ),
      ],
    );
  }

  InputDecoration _buildDecoration(BuildContext context) {
    return InputDecoration(
      hintText: DynamicLanguage.isLoading
          ? ""
          : DynamicLanguage.key(
              widget.hintText,
            ),
      hintStyle: TextStyle(
        fontSize: Dimensions.headingTextSize3,
        fontWeight: FontWeight.w500,
        color: CustomColor.primaryLightTextColor.withValues(alpha: 0.2),
      ),
      border: _setBorderStyle(BSS.b, context),
      enabledBorder: _setBorderStyle(BSS.enabledBorder, context),
      focusedBorder: _setBorderStyle(BSS.focusedBorder, context),
      disabledBorder: _setBorderStyle(BSS.disableBorder, context),
      errorBorder: _setBorderStyle(BSS.errorBorder, context),
      focusedErrorBorder: _setBorderStyle(BSS.focusedErrorBorder, context),
      prefixIcon: _setPrefixIcon(context),
      suffixIcon: _setSuffixIcon(context),
      fillColor: _setFillColor(context),
      filled: _setFilled(),
      contentPadding: _setPadding(),
    );
  }

  Widget _buildTitle(BuildContext context) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.start,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        verticalSpace(Dimensions.marginBetweenInputTitleAndBox),
      ],
    );
  }

  BorderSide _setBorderSide(BSS borderSideStyle, BuildContext context) {
    switch (borderSideStyle) {
      case BSS.enabledBorder:
      case BSS.b:
      case BSS.disableBorder:
        return BorderSide(
          width: widget.borderWidth,
          color: CustomColor.primaryLightTextColor.withValues(alpha: 0.2),
        );
      case BSS.focusedBorder:
        return BorderSide(
          width: widget.borderWidth,
          color: Theme.of(context).primaryColor,
        );
      case BSS.errorBorder:
      case BSS.focusedErrorBorder:
        return BorderSide(
          width: widget.borderWidth,
          color: Colors.red,
        );
      default:
        return BorderSide(
          width: widget.borderWidth,
          color: CustomColor.primaryLightTextColor.withValues(alpha: 0.2),
        );
    }
  }

  InputBorder _setBorderStyle(BSS borderSideStyle, BuildContext context) {
    switch (widget.borderStyle) {
      case BorderStyle.outline:
        return OutlineInputBorder(
          borderRadius: _setOutlineBorderRadius(),
          borderSide: widget.showBorderSide
              ? _setBorderSide(borderSideStyle, context)
              : BorderSide.none,
        );
      case BorderStyle.underline:
        return UnderlineInputBorder(
          borderRadius: _setOutlineBorderRadius(),
          borderSide: widget.showBorderSide
              ? _setBorderSide(borderSideStyle, context)
              : BorderSide.none,
        );
      case BorderStyle.none:
      default:
        return InputBorder.none;
    }
  }

  Color _setFillColor(BuildContext context) {
    return widget.fillColor ?? const Color(0xffF5F5F5);
  }

  bool _setFilled() {
    return widget.isFilled;
  }

  TextStyle _setFontStyle(BuildContext context) {
    return CustomStyle.darkHeading3TextStyle.copyWith(
      color: CustomColor.primaryLightTextColor,
    );
  }

  BorderRadius _setOutlineBorderRadius() {
    return BorderRadius.circular(widget.radius ?? Dimensions.radius * 1.2);
  }

  EdgeInsetsGeometry _setPadding() {
    return widget.customPadding ??
        (widget.padding == null
            ? EdgeInsets.symmetric(
                horizontal: Dimensions.widthSize,
                vertical: Dimensions.widthSize,
              )
            : EdgeInsets.all(widget.padding!));
  }

  Widget? _setPrefixIcon(BuildContext context) {
    return widget.prefixIcon ??
        (widget.prefixIconPath != ''
            ? Padding(
                padding: EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSize * 0.4),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CustomImageWidget(
                        path: widget.prefixIconPath!,
                        color: focusNode!.hasFocus
                            ? CustomColor.primaryLightTextColor
                            : (widget.hintText.isEmpty
                                ? CustomColor.liteBlack2
                                : CustomColor.liteBlack2)),
                    Visibility(
                      visible: widget.phoneCode != '',
                      child: Row(
                        children: [
                          Text(
                            widget.phoneCode,
                            style: TextStyle(
                              fontSize: Dimensions.headingTextSize3,
                              fontWeight: FontWeight.w500,
                              color: focusNode!.hasFocus
                                  ? Theme.of(context).primaryColor
                                  : CustomColor.primaryLightTextColor
                                      .withValues(alpha: 0.2),
                            ),
                          ),
                          horizontalSpace(Dimensions.paddingSize * 0.4),
                        ],
                      ),
                    ),
                  ],
                ),
              )
            : null);
  }

  Widget? _setSuffixIcon(BuildContext context) {
    return widget.isPasswordField
        ? IconButton(
            icon: Icon(
              isVisibility
                  ? Icons.visibility_off_outlined
                  : Icons.visibility_outlined,
              color: focusNode!.hasFocus
                  ? CustomColor.blackColor
                  : Get.isDarkMode
                      ? CustomColor.primaryDarkTextColor.withValues(alpha: 0.50)
                      : CustomColor.primaryLightTextColor.withValues(alpha: 0.50),
              size: Dimensions.iconSizeDefault,
            ),
            onPressed: () {
              setState(() {
                isVisibility = !isVisibility;
              });
            },
          )
        : widget.suffixIcon;
  }

  String? Function(String?)? _setValidator() {
    return widget.validator
        ? (value) {
            return (value == null || value.isEmpty)
                ? 'Please provide your credentials to proceed'
                : null;
          }
        : null;
  }
}
