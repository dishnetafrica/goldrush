import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../languages/language_drop_down.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../../widgets/common/app_bar/back_button.dart';
import '../../widgets/common/app_bar/primary_app_bar.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';

class SettingScreenMobile extends StatelessWidget {
  const SettingScreenMobile({super.key});

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
        appBar: PrimaryAppBar(
          DynamicLanguage.key(Strings.settings),
          showBackButton: true,
          leading: BackButtonWidget(
            onTap: () {
              Get.offAllNamed(Routes.home);
            },
          ),
        ),
        body: _bodyWidget(context),
      ),
    );
  }

  Padding _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSize),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          _changeLanguageWidget(context),
        ],
      ),
    );
  }

  Card _changeLanguageWidget(BuildContext context) {
    return Card(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(Dimensions.radius),
      ),
      elevation: 4,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                TitleHeading3Widget(
                    text: DynamicLanguage.key(Strings.changeLanguage)),
              ],
            ),
            // Language selection dropdown or text with arrow
            const Flexible(
              child: ChangeLanguageWidget(
                routeOnChange: Routes.settings,
                dorpButtonColor: CustomColor.whiteColor,
                dropTextColor: CustomColor.primaryLightTextColor,
                dropMenuColor: CustomColor.whiteColor,
                arrowColor: CustomColor.blackColor,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
