import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:get/get.dart';

import '../../controller/basic_settings_controller/basic_settings_controller.dart';
import '../../controller/invest/invest_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/custom_style.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../web_view_widget/web_view_screen.dart';

class AgreedWidget extends StatefulWidget {
  final String agreedText;
  final String termsText;
  final bool? isSelected;

  const AgreedWidget({
    super.key,
    required this.agreedText,
    required this.termsText,
    this.isSelected,
  });

  @override
  _AgreedWidgetState createState() => _AgreedWidgetState();
}

class _AgreedWidgetState extends State<AgreedWidget> {
  bool? agreed;

  @override
  void initState() {
    super.initState();
    agreed = widget.isSelected ?? false;
  }

  @override
  Widget build(BuildContext context) {
    final controller = Get.find<InvestPlanController>();
    return Container(
      margin: EdgeInsets.only(top: Dimensions.heightSize * 0.66),
      child: Row(
        children: [
          SizedBox(
            height: 24.0,
            width: 24.0,
            child: Obx(() {
              return Checkbox(
                value: controller.isSelected.value,
                onChanged: (value) {
                  controller.isSelected.value = value ?? false;
                },
                activeColor: Theme.of(context).primaryColor,
                checkColor: controller.isSelected.value
                    ? Theme.of(context).colorScheme.surface
                    : null,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(Dimensions.radius * 0.2),
                ),
                side: BorderSide(
                  color: Theme.of(context).brightness == Brightness.dark
                      ? CustomColor.primaryDarkTextColor.withValues(alpha: 0.50)
                      : CustomColor.primaryLightTextColor.withValues(alpha: 0.50),
                ),
              );
            }),
          ),
          horizontalSpace(Dimensions.widthSize),
          RichText(
            text: TextSpan(
              text: DynamicLanguage.isLoading
                  ? ""
                  : DynamicLanguage.key(widget.agreedText),
              style: Theme.of(context).brightness == Brightness.dark
                  ? CustomStyle.darkHeading5TextStyle
                  : CustomStyle.lightHeading5TextStyle,
              recognizer: TapGestureRecognizer()
                ..onTap = () {
                  controller.isSelected.value = !(controller.isSelected.value);
                },
              children: [
                WidgetSpan(
                  child: Padding(
                    padding: EdgeInsets.symmetric(
                      horizontal: Dimensions.marginSizeHorizontal * 0.1,
                    ),
                  ),
                ),
                WidgetSpan(
                  child: Padding(
                    padding: EdgeInsets.symmetric(
                      horizontal: Dimensions.marginSizeHorizontal * 0.001,
                    ),
                  ),
                ),
                TextSpan(
                  text: DynamicLanguage.isLoading
                      ? ""
                      : DynamicLanguage.key(widget.termsText),
                  style: Theme.of(context).brightness == Brightness.dark
                      ? CustomStyle.darkHeading5TextStyle.copyWith(
                          color: Theme.of(context).primaryColor,
                        )
                      : CustomStyle.lightHeading5TextStyle.copyWith(
                          color: Theme.of(context).primaryColor,
                        ),
                  recognizer: TapGestureRecognizer()
                    ..onTap = () {
                      Get.to(() => WebViewScreen(
                            title: Strings.privacyPolicy,
                            url: BasicServices.privacyPolicy.value,
                          ));
                    },
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
