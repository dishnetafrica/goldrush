part of 'reset_password_screen.dart';

class ResetPasswordMobileScreenLayout extends GetView<ResetPasswordController> {
  ResetPasswordMobileScreenLayout({super.key});

  final formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
        backgroundColor: CustomColor.whiteColor,
        body: SingleChildScrollView(
          child: Column(
            children: [
              verticalSpace(Dimensions.heightSize * 10),
              _bodyWidget(context),
            ],
          ),
        ));
  }

  Padding _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      child: Column(
        crossAxisAlignment: crossStart,
        children: [
          const TitleSubTitleWidget(
            title: Strings.resetPassword,
            subTitle: Strings.restPassDet,
          ),
          verticalSpace(Dimensions.heightSize * 2),
          _inputFieldWidget(context),
          _buttonWidget(context)
        ],
      ),
    );
  }

  Form _inputFieldWidget(BuildContext context) {
    return Form(
      key: formKey,
      child: Column(
        children: [
          PrimaryInputWidget(
            textController: controller.newPasswordController,
            validator: true,
            hintText: Strings.newPass,
            prefixIconPath: Assets.icon.key,
            isPasswordField: true,
            textInputType: TextInputType.text,
          ),
          verticalSpace(Dimensions.heightSize * 0.8),
          PrimaryInputWidget(
            textController: controller.confirmPasswordController,
            validator: true,
            hintText: Strings.confirmPassword,
            prefixIconPath: Assets.icon.key,
            isPasswordField: true,
            textInputType: TextInputType.text,
          ),
        ],
      ),
    );
  }

  Padding _buttonWidget(BuildContext context) {
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
                    title: Strings.resetPass,
                    fontSize: Dimensions.headingTextSize3,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      controller.onResetPassword;
                    },
                    buttonTextColor: CustomColor.whiteColor,
                    buttonColor: CustomColor.primaryLightColor,
                    elevation: 0,
                    borderColor: Theme.of(context).primaryColor,
                    borderWidth: 1.5,
                    radius: Dimensions.radius * 1.2,
                    height: Dimensions.heightSize * 4.67,
                  ),
          )
        ],
      ),
    );
  }
}
