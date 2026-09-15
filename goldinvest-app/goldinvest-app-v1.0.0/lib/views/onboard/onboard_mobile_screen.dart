part of 'onboard_screen.dart';

class OnboardMobileScreen extends GetView<OnboardController> {
  const OnboardMobileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Obx(
        () => BasicServices.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  SafeArea _bodyWidget(BuildContext context) {
    final w = MediaQuery.of(context).size.width;
    final h = MediaQuery.of(context).size.height;

    return SafeArea(
      child: Stack(
        alignment: Alignment.bottomCenter,
        children: [
          _clipViewWidget(context),
          Positioned(
              top: Dimensions.heightSize * 0.15,
              bottom: h * 0.85,
              right: Dimensions.widthSize,
              left: -w * 0.57,
              child: SizedBox(
                child: FittedBox(
                  fit: BoxFit.contain,
                  child: CachedNetworkImage(
                    height: MediaQuery.of(context).size.height,
                    imageUrl:
                        "${BasicServices.appImageBasePath.value}/${BasicServices.appImagepathLocation.value}/${BasicServices.appLogo.value}",
                    placeholder: (context, url) => Container(),
                    errorWidget: (context, url, error) => Container(),
                  ),
                ),
              )),
          Positioned(
              top: Dimensions.heightSize * 0.15,
              bottom: h * 0.85,
              right: Dimensions.widthSize,
              left: w * 0.57,
              child: SizedBox(
                child: FittedBox(
                    fit: BoxFit.contain,
                    child: Padding(
                      padding: EdgeInsets.all(Dimensions.paddingSize),
                      child: const ChangeLanguageWidget(
                        routeOnChange: Routes.onboardScreen,
                      ),
                    )),
              )),
          Positioned(
              bottom: Dimensions.heightSize * 0.83,
              right: Dimensions.widthSize * 5,
              left: Dimensions.widthSize * 5,
              child: Column(
                children: [
                  _indicatorWidget(context),
                  Padding(
                    padding: EdgeInsets.symmetric(
                      vertical: Dimensions.marginSizeVertical * 1.6,
                    ),
                    child: Column(
                      children: [
                        _buttonLoginWidget(context),
                        verticalSpace(Dimensions.paddingSize),
                        _buttonRegisterWidget(context),
                      ],
                    ),
                  ),
                ],
              )),
        ],
      ),
    );
  }

  Stack _clipViewWidget(BuildContext context) {
    return Stack(
      alignment: Alignment.center,
      children: [
        PageView.builder(
          itemCount: BasicServices.onboardScreen.length,
          itemBuilder: (_, i) {
            var data = BasicServices.onboardScreen[i];
            return Stack(children: [
              Align(
                alignment: Alignment.center,
                child: CachedNetworkImage(
                  height: MediaQuery.of(context).size.height,
                  imageUrl:
                      "${BasicServices.basePath.value}/${BasicServices.pathLocation.value}/${data.image}",
                  placeholder: (context, url) => Container(),
                  errorWidget: (context, url, error) => Container(),
                ),
              ),
              Positioned(
                top: Dimensions.heightSize * 5,
                bottom: Dimensions.heightSize * 0.83,
                right: Dimensions.widthSize * 4.3,
                left: Dimensions.widthSize * 3,
                child: TitleHeading1Widget(
                  text: data.title!,
                  fontSize: Dimensions.headingTextSize3 * 1.9,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ]);
          },
          onPageChanged: (v) {
            controller.selectedIndex.value = v;
          },
        )
      ],
    );
  }

  Row _indicatorWidget(BuildContext context) {
    return Row(
      mainAxisAlignment: mainCenter,
      children: List.generate(
        BasicServices.onboardScreen.length,
        (index) => Container(
          height: index == controller.selectedIndex.value
              ? Dimensions.heightSize * 0.9
              : Dimensions.heightSize * 0.8,
          alignment: Alignment.center,
          width: index != controller.selectedIndex.value
              ? Dimensions.widthSize * 2
              : Dimensions.widthSize * 2,
          decoration: BoxDecoration(
            color: index == controller.selectedIndex.value
                ? CustomColor.primaryLightColor
                : CustomColor.primaryLightColor.withValues(alpha: 0.5),
            borderRadius: index != controller.selectedIndex.value
                ? null
                : BorderRadius.circular(Dimensions.radius * 2),
            shape: index != controller.selectedIndex.value
                ? BoxShape.circle
                : BoxShape.rectangle,
          ),
        ),
      ),
    );
  }

  //login button
  PrimaryButton _buttonLoginWidget(context) {
    return PrimaryButton(
      title: DynamicLanguage.isLoading
          ? ""
          : DynamicLanguage.key(Strings.loginNow),
      buttonTextColor: CustomColor.whiteColor,
      radius: Dimensions.radius * 1.2,
      onPressed: () {
        Get.toNamed(Routes.signInScreen);
      },
    );
  }

  Widget _buttonRegisterWidget(context) {
    return BasicServices.registrationEnable.value == 1
        ? PrimaryButton(
            borderColor: CustomColor.secondaryDarkColor,
            buttonColor: CustomColor.secondaryDarkColor,
            title: DynamicLanguage.isLoading
                ? ""
                : DynamicLanguage.key(Strings.registration),
            buttonTextColor: CustomColor.primaryLightColor,
            radius: Dimensions.radius * 1.2,
            onPressed: () {
              Get.toNamed(Routes.signUpScreen);
            },
          )
        : const SizedBox.shrink();
  }
}
