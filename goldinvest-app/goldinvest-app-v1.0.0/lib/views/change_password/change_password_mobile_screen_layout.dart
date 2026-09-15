part of 'change_password_screen.dart';

class ChangePasswordMobileScreen extends StatelessWidget {
  ChangePasswordMobileScreen({super.key});
  final controller = Get.put(ChangePasswordController());

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
        appBar: PrimaryAppBar(
          Strings.changePassword,
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

  SingleChildScrollView _bodyWidget(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        children: [_inputFieldWidget(context)],
      ),
    );
  }

  Form _inputFieldWidget(BuildContext context) {
    return Form(
      child: Padding(
          padding: EdgeInsets.all(Dimensions.paddingSize),
          child: Column(
            children: [
              PrimaryInputWidget(
                textController: controller.changePasswordController,
                validator: true,
                hintText: Strings.currentPassword,
                prefixIconPath: Assets.icon.key,
                isPasswordField: true,
                textInputType: TextInputType.text,
              ),
              verticalSpace(Dimensions.marginBetweenInputTitleAndBox * 1.2),
              PrimaryInputWidget(
                textController: controller.passwordController,
                validator: true,
                hintText: Strings.newPassword,
                prefixIconPath: Assets.icon.key,
                isPasswordField: true,
                textInputType: TextInputType.text,
              ),
              verticalSpace(Dimensions.marginBetweenInputTitleAndBox * 1.2),
              PrimaryInputWidget(
                textController: controller.passwordConfirmationController,
                validator: true,
                hintText: Strings.confirmPassword,
                prefixIconPath: Assets.icon.key,
                isPasswordField: true,
                textInputType: TextInputType.text,
              ),
              _buttonWidget(context),
            ],
          )),
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
                    title: Strings.changePassword,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      controller.onChangePassword();
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
