part of 'sign_in_screen.dart';

// ignore: must_be_immutable
class SignInMobileScreenLayout extends GetView<SignInController> {
  SignInMobileScreenLayout({super.key});

  final formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Scaffold(
          backgroundColor: CustomColor.whiteColor, body: _bodyWidget(context)),
    );
  }

  SingleChildScrollView _bodyWidget(context) {
    return SingleChildScrollView(
      child: Padding(
        padding: EdgeInsets.all(Dimensions.paddingSize * 1.2),
        child: Column(
          crossAxisAlignment: crossStart,
          mainAxisAlignment: mainStart,
          children: [
            verticalSpace(Dimensions.marginSizeVertical * 2),
            BackButtonWidget(
              padding: EdgeInsets.zero,
              onTap: () => {Get.toNamed(Routes.onboardScreen)},
            ),
            verticalSpace(Dimensions.marginSizeVertical),
            _welcomeTextWidget(context),
            verticalSpace(Dimensions.marginSizeVertical * 1.5),
            _inputFieldWidget(context),
          ],
        ),
      ),
    );
  }

  Column _welcomeTextWidget(BuildContext context) {
    return const Column(
      children: [
        TitleSubTitleWidget(
          title: Strings.welcome,
          subTitle: Strings.welcome1,
        ),
      ],
    );
  }

  Form _inputFieldWidget(BuildContext context) {
    return Form(
      key: formKey,
      child: Column(
        children: [
          Column(
            children: [
              PrimaryInputWidget(
                textController: controller.emailAddressController,
                validator: true,
                hintText: Strings.inputEmail,
                prefixIconPath: Assets.icon.sms,
                textInputType: TextInputType.emailAddress,
              ),
            ],
          ),
          verticalSpace(Dimensions.heightSize * 0.8),
          Column(
            children: [
              PrimaryInputWidget(
                validator: true,
                textController: controller.passwordController,
                hintText: Strings.inputPass,
                prefixIconPath: Assets.icon.key,
                isPasswordField: true,
                textInputType: TextInputType.text,
              ),
            ],
          ),
          verticalSpace(Dimensions.marginBetweenInputTitleAndBox * 0.6),
          _isForgetPasswordWidget(context),
          _buttonWidget(context),
          _doNotHaveAnAccount(context)
        ],
      ),
    );
  }

  SignInBottomSheet _isForgetPasswordWidget(BuildContext context) {
    return SignInBottomSheet();
  }

  Padding _buttonWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical,
      ),
      child: Column(
        children: [
          Obx(
            () => controller.isLoadingSignIn
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.loginNow,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      if (formKey.currentState!.validate()) {
                        controller.signInProcess();
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

  Padding _doNotHaveAnAccount(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 0.5),
      child: CustomTextSpanWidget(
        firstText: Strings.donHaveAccount,
        firstTextColor: CustomColor.blackColor.withValues(alpha: 0.4),
        firstFontWeight: FontWeight.w500,
        secondText: Strings.registerNow,
        secondTextColor: CustomColor.primaryLightColor,
        onSecondTextTap: () {
          Get.toNamed(Routes.signUpScreen);
        },
      ),
    );
  }
}
