part of 'two_fa_security_screen.dart';

class TwoFaMobileScreenLayout extends StatelessWidget {
  TwoFaMobileScreenLayout({super.key});

  final controller = Get.put(TwoFaVerificationController());

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
          appBar: PrimaryAppBar(
            showBackButton: true,
            Strings.faSecurity,
            leading: BackButtonWidget(
              onTap: () {
                Get.toNamed(Routes.home);
              },
            ),
            // autoLeading: true,
          ),
          body: Column(
            children: [
              Obx(
                () => controller.isLoading
                    ? const CustomLoadingAPI()
                    : _bodyWidget(context),
              ),
            ],
          )),
    );
  }

  Expanded _bodyWidget(BuildContext context) {
    return Expanded(
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            child: Column(
              children: [_qrWidget(context), _twoFaInfo(context)],
            ),
          )
        ],
      ),
    );
  }

  Image _qrWidget(context) {
    return Image.network(
      controller.qrCode.value,
      height: Dimensions.heightSize * 20,
      width: Dimensions.widthSize * 31.1,
    );
  }

  Padding _twoFaInfo(context) {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingSize),
      child: Column(
        children: [
          TitleSubTitleWidget(
              title: Strings.enableTwoFaSecurity,
              subTitle: Strings.wellAskFor,
              subTitleColor: CustomColor.blackColor.withValues(alpha: 0.4)),
          Padding(
            padding: EdgeInsets.only(top: Dimensions.paddingSize),
            child: PrimaryInputWidget(
              textController: controller.qrSecret,
              readOnly: true,
              hintText: " ",
              prefixIconPath: Assets.icon.link2,
              suffixIcon: Padding(
                padding: EdgeInsets.all(Dimensions.paddingSize * 0.4),
                child: GestureDetector(
                  child: CustomImageWidget(path: Assets.icon.document),
                  onTap: () async {
                    Clipboard.setData(
                            ClipboardData(text: controller.qrSecret.text))
                        .then((_) {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                          content: Text(
                        DynamicLanguage.key(Strings.textCopy),
                        style: const TextStyle(color: Colors.red),
                      )));
                    });
                  },
                ),
              ),
            ),
          ),
          _buttonWidget(context)
        ],
      ),
    );
  }

  Padding _buttonWidget(context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical,
      ),
      child: Obx(
        () => controller.isSubmitLoading
            ? const CustomLoadingAPI()
            : PrimaryButton(
                title: controller.status.value == 0
                    ? Strings.enable
                    : Strings.disable,
                fontWeight: FontWeight.w600,
                onPressed: () {
                  controller.onEnableOrDisable;
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
    );
  }
}
