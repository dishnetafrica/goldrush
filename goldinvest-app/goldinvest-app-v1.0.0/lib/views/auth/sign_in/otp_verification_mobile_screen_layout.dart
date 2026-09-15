part of 'otp_verification_screen.dart';

class ForgetOtpVerificationMobileScreen
    extends GetView<OtpVerificationController> {
  const ForgetOtpVerificationMobileScreen({super.key});

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
          children: [
            verticalSpace(Dimensions.marginSizeVertical * 2),
            BackButtonWidget(
              padding: EdgeInsets.zero,
              onTap: () => {Get.close(1)},
            ),
            verticalSpace(Dimensions.heightSize * 4),
            _otpWidget(context),
          ],
        ),
      ),
    );
  }

  Column _otpWidget(BuildContext context) {
    return Column(
      crossAxisAlignment: crossStart,
      children: [
        const TitleSubTitleWidget(
          title: Strings.pleaseEnterCode,
          subTitle: Strings.digit6,
        ),
        _otpInputWidget(context),
        _timeWidget(context),
        _resendButton(context),
        _buttonWidget(context)
      ],
    );
  }

  Container _otpInputWidget(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(top: Dimensions.paddingSize * 1.5),
      child: PinCodeTextField(
        appContext: context,
        backgroundColor: Colors.transparent,
        textStyle: Get.isDarkMode
            ? CustomStyle.darkHeading2TextStyle
            : CustomStyle.lightHeading2TextStyle,
        enableActiveFill: true,
        length: 6,
        obscureText: false,
        blinkWhenObscuring: true,
        animationType: AnimationType.fade,
        validator: (v) {
          if (v!.length < 3) {
            return DynamicLanguage.key(Strings.pleaseFillOutTheField);
          } else {
            return null;
          }
        },
        pinTheme: PinTheme(
          shape: PinCodeFieldShape.box,
          fieldHeight: Dimensions.heightSize * 4,
          fieldWidth: Dimensions.widthSize * 5,
          inactiveColor: Colors.transparent,
          activeColor: Colors.transparent,
          selectedColor: Colors.transparent,
          inactiveFillColor: CustomColor.blackColor.withValues(alpha: 0.1),
          activeFillColor: Theme.of(context).colorScheme.surface,
          selectedFillColor: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(Dimensions.radius * 0.8),
        ),
        cursorColor: CustomColor.blackColor,
        animationDuration: const Duration(milliseconds: 300),
        keyboardType: TextInputType.number,
        controller: controller.pinCodeController,
        onCompleted: (v) {},
        onChanged: (value) {},
        beforeTextPaste: (text) {
          return true;
        },
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
                    title: Strings.submit,
                    fontSize: Dimensions.headingTextSize3,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      controller.onOTPVerification;
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

  Obx _timeWidget(BuildContext context) {
    return Obx(() => Padding(
          padding: EdgeInsets.only(
            top: Dimensions.paddingSize,
            bottom: Dimensions.paddingSize,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.watch_later_outlined,
                color: Theme.of(context).primaryColor,
              ),
              horizontalSpace(Dimensions.widthSize * 0.5),
              TitleHeading4Widget(
                text: controller.secondsRemaining >= 0 &&
                        controller.secondsRemaining <= 9
                    ? '00:0${controller.secondsRemaining.value}'
                    : '00:${controller.secondsRemaining.value}',
                fontWeight: FontWeight.w600,
              ),
            ],
          ),
        ));
  }

  Obx _resendButton(BuildContext context) {
    return Obx(
      () => Visibility(
        visible: controller.enableResend.value,
        child: InkWell(
          onTap: () {
            controller.resendCode();
          },
          child: const TitleHeading4Widget(text: Strings.resendCode),
        ),
      ),
    );
  }
}
