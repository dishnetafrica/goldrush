part of 'sign_up_mobile_screen.dart';

class SignUpMobileScreenLayout extends StatelessWidget {
  SignUpMobileScreenLayout({super.key});
  final controller = Get.put(RegistrationController());
  final referralController = Get.put(BasicServices());

  final formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _bodyWidget(context),
    );
  }

  SingleChildScrollView _bodyWidget(BuildContext context) {
    return SingleChildScrollView(
      child: Padding(
        padding: EdgeInsets.all(Dimensions.paddingSize),
        child: Column(
          crossAxisAlignment: crossStart,
          mainAxisAlignment: mainStart,
          children: [
            verticalSpace(Dimensions.marginSizeVertical * 2),
            BackButtonWidget(
              padding: EdgeInsets.zero,
              onTap: () => {Get.offAllNamed(Routes.onboardScreen)},
            ),
            _welcomeTextWidget(context),
            _inputFieldWidget(context),
            _agreedWidget(context),
            _buttonWidget(context),
            _alreadyHaveAccountWidget(context),
          ],
        ),
      ),
    );
  }

  Padding _welcomeTextWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
      child: const TitleSubTitleWidget(
        title: Strings.registerForAndAccount,
        subTitle: Strings.registrationTitle,
      ),
    );
  }

  Padding _alreadyHaveAccountWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: Dimensions.paddingSize * 1.5),
      child: Align(
        alignment: Alignment.center,
        child: CustomTextSpanWidget(
          firstText: Strings.allReadyHave,
          firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
          firstFontWeight: FontWeight.w500,
          secondText: Strings.loginNow,
          secondTextColor: CustomColor.primaryLightColor,
          onSecondTextTap: () {
            Get.offAllNamed(Routes.signInScreen);
          },
        ),
      ),
      // child: Align(
    );
  }

  Form _inputFieldWidget(BuildContext context) {
    return Form(
      key: formKey,
      child: Padding(
        padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: PrimaryInputWidget(
                    textController: controller.firstNameController,
                    validator: true,
                    hintText: Strings.firstName,
                    prefixIconPath: Assets.icon.user,
                    textInputType: TextInputType.emailAddress,
                  ),
                ),
                horizontalSpace(Dimensions.widthSize),
                Expanded(
                  child: PrimaryInputWidget(
                    textController: controller.lastNameController,
                    validator: true,
                    hintText: Strings.lastName,
                    prefixIconPath: Assets.icon.user,
                    textInputType: TextInputType.emailAddress,
                  ),
                ),
              ],
            ),
            PrimaryInputWidget(
              textController: controller.emailAddressController,
              validator: true,
              hintText: Strings.inputEmail,
              prefixIconPath: Assets.icon.sms,
              textInputType: TextInputType.emailAddress,
            ),
            PrimaryInputWidget(
              textController: controller.passwordController,
              validator: true,
              isPasswordField: true,
              hintText: Strings.inputPass,
              prefixIconPath: Assets.icon.key,
              textInputType: TextInputType.emailAddress,
            ),
            BasicServices.referralEnable.value == 1
                ? PrimaryInputWidget(
                    textController: controller.referralIdController,
                    hintText: Strings.referralId,
                    prefixIconPath: Assets.icon.profile2userSvg,
                    textInputType: TextInputType.emailAddress,
                  )
                : const SizedBox.shrink(),
          ],
        ),
      ),
    );
  }

  Padding _buttonWidget(context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical,
      ),
      child: Column(
        children: [
          Obx(
            () => controller.isLoading
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.registerNow,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      if (formKey.currentState!.validate()) {
                        if (BasicServices.agreePolicyEnable.value == 1) {
                          if (controller.agree.value) {
                            controller.onRegistration;
                          } else {
                            CustomSnackBar.error("please agree");
                          }
                        } else {
                          controller.onRegistration;
                        }
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
        ],
      ),
    );
  }

  Widget _agreedWidget(BuildContext context) {
    return BasicServices.agreePolicyEnable.value == 1
        ? const SignUpWidget()
        : const SizedBox.shrink();
  }
}
