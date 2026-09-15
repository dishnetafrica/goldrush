import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/utils/custom_loading_api.dart';
import 'package:goldinvest/controller/auth/sign_in/sign_in_controller.dart';
import 'package:goldinvest/languages/strings.dart';
import 'package:goldinvest/utils/custom_color.dart';
import 'package:goldinvest/utils/size.dart';
import 'package:goldinvest/widgets/common/others/custom_image_widget.dart';

import '../../../custom_assets/assets.gen.dart';
import '../../../utils/dimensions.dart';
import '../../../widgets/common/buttons/primary_button.dart';
import '../../../widgets/common/inputs/primary_input_widget.dart';
import '../../../widgets/common/text_labels/title_heading5_widget.dart';
import '../../../widgets/common/text_labels/title_sub_title_widget.dart';

class SignInBottomSheet extends GetView<SignInController> {
  SignInBottomSheet({super.key});
  final formKeyForgetPass = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: mainEnd,
      children: [
        InkWell(
          onTap: () {
            _showBottomSlider(context);
          },
          child: TitleHeading5Widget(
            text: Strings.forgetPass,
            fontWeight: FontWeight.w600,
            color: Theme.of(context).primaryColor,
          ),
        ),
      ],
    );
  }

  void _showBottomSlider(BuildContext context) {
    showModalBottomSheet(
      isScrollControlled: true,
      backgroundColor: CustomColor.whiteColor,
      context: context,
      shape: RoundedRectangleBorder(
        borderRadius:
            BorderRadius.vertical(top: Radius.circular(Dimensions.radius * 2)),
      ),
      builder: (BuildContext context) {
        return Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
          ),
          child: Form(
            key: formKeyForgetPass,
            child: Wrap(
              children: [
                Padding(
                  padding: EdgeInsets.all(
                    Dimensions.paddingSize,
                  ),
                  child: Column(
                    crossAxisAlignment: crossStart,
                    children: [
                      Align(
                        alignment: Alignment.center,
                        child: CustomImageWidget(
                          path: Assets.icon.slideBarRectangle,
                          height: Dimensions.heightSize * 0.5,
                          width: Dimensions.widthSize * 3.5,
                        ),
                      ),
                      verticalSpace(Dimensions.heightSize * 2),
                      const TitleSubTitleWidget(
                        title: Strings.resetForgottenPass,
                        subTitle: Strings.resetForgottenPassDet,
                      ),
                      verticalSpace(Dimensions.heightSize * 0.5),
                      PrimaryInputWidget(
                        textController:
                            controller.forgotPasswordEmailAddressController,
                        validator: true,
                        hintText: Strings.inputEmail,
                        prefixIconPath: Assets.icon.sms,
                        textInputType: TextInputType.emailAddress,
                      ),
                      _sliderButtonWIdget(context)
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Padding _sliderButtonWIdget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical,
      ),
      child: Column(
        children: [
          Obx(
            () => controller.isLoadingForgetPassword
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.forgotPass,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      if (formKeyForgetPass.currentState!.validate()) {
                        controller.onForgetPassword;
                      }
                    },
                    buttonTextColor: CustomColor.whiteColor,
                    buttonColor: CustomColor.primaryLightColor,
                    elevation: 0,
                    borderColor: Theme.of(context).primaryColor,
                    borderWidth: 1.5,
                    radius: Dimensions.radius * 1.2,
                    height: Dimensions.heightSize * 4.67,
                  ),
          ),
          verticalSpace(Dimensions.marginBetweenInputTitleAndBox * 2),
        ],
      ),
    );
  }
}
