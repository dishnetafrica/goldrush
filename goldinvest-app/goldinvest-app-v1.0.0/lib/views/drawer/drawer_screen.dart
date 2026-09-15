import 'dart:io';

import 'package:dotted_border/dotted_border.dart';
import 'package:dynamic_languages/dynamic_languages.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/utils/custom_loading_api.dart';
import 'package:goldinvest/controller/auth/sign_in/sign_in_controller.dart';
import 'package:goldinvest/controller/profile/profile_controller.dart';
import 'package:goldinvest/utils/size.dart';
import 'package:goldinvest/widgets/common/text_labels/title_heading4_widget.dart';
import 'package:goldinvest/widgets/web_view_widget/web_view_screen.dart';

import '../../controller/basic_settings_controller/basic_settings_controller.dart';
import '../../languages/strings.dart';
import '../../routes/routes.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/buttons/primary_button.dart';
import '../../widgets/common/text_labels/title_heading3_widget.dart';
import '../../widgets/common/text_labels/title_heading5_widget.dart';

class DrawerScreen extends StatelessWidget {
  DrawerScreen({super.key});
  final profileController = Get.put(ProfileController());
  final signInController = Get.put(SignInController());

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Drawer(
        width: MediaQuery.of(context).size.width * 0.6,
        elevation: 3,
        backgroundColor: CustomColor.primaryBGLightColor,
        child: Padding(
          padding: EdgeInsets.all(Dimensions.paddingSize),
          child: ListView(
            children: [
              _appLogoWidget(context),
              _userInfoWidget(context),
              _drawerTitle(context),
              _buttonWidget(context),
            ],
          ),
        ),
      ),
    );
  }

  Align _appLogoWidget(BuildContext context) {
    return Align(
      alignment: Alignment.topLeft,
      child: InkWell(
        onTap: () {
          Get.close(1);
        },
        child: const Icon(Icons.arrow_back),
      ),
    );
  }

  Obx _userInfoWidget(BuildContext context) {
    return Obx(
      () => Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize),
        child: Column(
          crossAxisAlignment: crossStart,
          children: [
            DottedBorder(
              options: RoundedRectDottedBorderOptions(
                radius: Radius.circular(Dimensions.radius * 4),
                dashPattern: const [3, 1],
               
                color: CustomColor.primaryLightColor,
                strokeWidth: 2,
              ),

              child: profileController.imagePath.value.isNotEmpty
                  ? ClipOval(
                      child: Image.file(
                        File(profileController.imagePath.value),
                        height: Dimensions.radius * 8,
                        width: Dimensions.radius * 8,
                        fit: BoxFit.cover,
                      ),
                    )
                  : ClipOval(
                      child: Image.network(
                        profileController.userImage.value,
                        height: Dimensions.radius * 8,
                        width: Dimensions.radius * 8,
                        fit: BoxFit.cover,
                      ),
                    ),
            ),
            Padding(
              padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
              child: TitleHeading3Widget(
                text: profileController.userFullName.value,
              ),
            ),
            Padding(
              padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.3),
              child: Container(
                alignment: Alignment.center,
                height: Dimensions.heightSize * 2.3,
                width: Dimensions.widthSize * 5.8,
                color: CustomColor.primaryLightColor.withValues(alpha: 0.1),
                child: TitleHeading5Widget(
                  text: profileController.userLevel.value,
                  color: CustomColor.primaryLightColor,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Padding _drawerTitle(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          TitleHeading4Widget(
            text: Strings.security,
            color: CustomColor.blackColor.withValues(alpha: 0.4),
          ),
          buildMenuItem(
            context,
            title: Strings.kycVerification,
            onTap: () {
              Get.offAllNamed(Routes.kycVerification);
            },
          ),
          buildMenuItem(
            context,
            title: Strings.faSecurity,
            onTap: () {
              Get.offAllNamed(Routes.twoFaSecurity);
            },
          ),
          buildMenuItem(
            context,
            title: Strings.changePassword,
            onTap: () {
              Get.offAllNamed(Routes.changePassword);
            },
          ),
          buildMenuItem(
            context,
            title: Strings.settings,
            onTap: () {
              Get.offAllNamed(Routes.settings);
            },
          ),
          Padding(
            padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.1),
            child: TitleHeading4Widget(
              text: Strings.general,
              color: CustomColor.blackColor.withValues(alpha: 0.4),
            ),
          ),
          buildMenuItem(
            context,
            title: Strings.helpCenter,
            onTap: () {
              Get.to(
                () => WebViewScreen(
                  title: Strings.helpCenter,
                  url: BasicServices.contactUs.value,
                ),
              );
            },
          ),
          buildMenuItem(
            context,
            title: Strings.privacyPolicy,
            onTap: () {
              Get.to(
                () => WebViewScreen(
                  title: Strings.privacyPolicy,
                  url: BasicServices.privacyPolicy.value,
                ),
              );
            },
          ),
          buildMenuItem(
            context,
            title: Strings.aboutUs,
            onTap: () {
              Get.to(
                () => WebViewScreen(
                  title: Strings.aboutUs,
                  url: BasicServices.aboutUs.value,
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Container _buttonWidget(context) {
    return Container(
      padding: EdgeInsets.only(
        right: Dimensions.widthSize * 5,
        top: Dimensions.paddingSize * 0.5,
      ),
      child: Obx(
        () => signInController.isLoadingLogout
            ? const CustomLoadingAPI()
            : PrimaryButton(
                title: DynamicLanguage.key(Strings.logOut),
                fontWeight: FontWeight.w600,
                fontSize: Dimensions.headingTextSize3,
                onPressed: () {
                  signInController.onSignOut;
                },
                buttonTextColor: CustomColor.whiteColor,
                buttonColor: CustomColor.primaryLightColor,
                elevation: 0,
                borderColor: Theme.of(context).primaryColor,
                borderWidth: 1.5,
                radius: Dimensions.radius * 1.2,
                height: Dimensions.heightSize * 3.5,
              ),
      ),
    );
  }

  Column buildMenuItem(
    context, {
    required String title,
    required VoidCallback onTap,
  }) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(),
          child: ListTile(
            minLeadingWidth: 0,
            contentPadding: EdgeInsets.zero,
            dense: true,
            title: TitleHeading3Widget(text: title),
            onTap: onTap,
          ),
        ),
        Divider(
          color: CustomColor.blackColor.withValues(alpha: 0.4),
          thickness: 0.1,
          height: 0.01,
        ),
      ],
    );
  }
}
