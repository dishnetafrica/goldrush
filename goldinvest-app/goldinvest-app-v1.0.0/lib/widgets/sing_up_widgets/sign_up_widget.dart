import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:get/get.dart';

import '../../languages/strings.dart';
import '../../../utils/custom_style.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../../controller/auth/sign_up/registration_controller.dart';
import '../check_box/custom_checkbox.dart';

class SignUpWidget extends GetView<RegistrationController> {
  const SignUpWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: Dimensions.heightSize * 0.66),
      child: Row(
        children: [
          SizedBox(
            height: 24.0,
            width: 24.0,
            child: CustomCheckbox(
              value: controller.agree,
              onChanged: (value) {
                controller.agree.value = value!;
              },
            ),
          ),
          horizontalSpace(Dimensions.widthSize),
          RichText(
            text: TextSpan(
              text: DynamicLanguage.key(
                Strings.iHaveAgreed,
              ),
              style: Theme.of(context).brightness == Brightness.dark
                  ? CustomStyle.darkHeading5TextStyle
                  : CustomStyle.lightHeading5TextStyle,
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
                  text: DynamicLanguage.key(
                    Strings.termsOfUse,
                  ),
                  style: Theme.of(context).brightness == Brightness.dark
                      ? CustomStyle.darkHeading5TextStyle.copyWith(
                          color: Theme.of(context).primaryColor,
                        )
                      : CustomStyle.lightHeading5TextStyle.copyWith(
                          color: Theme.of(context).primaryColor,
                        ),
                  recognizer: TapGestureRecognizer()..onTap = () {},
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
