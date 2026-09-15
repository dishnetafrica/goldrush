part of 'kyc_verification_screen.dart';

class KycVerificationMobileScreen extends StatelessWidget {
  KycVerificationMobileScreen({super.key});

  final controller = Get.put(KycInformationController());

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        Get.toNamed(Routes.home);
        return true;
      },
      child: Scaffold(
          appBar: PrimaryAppBar(
            Strings.kycVerification,
            showBackButton: true,
            backgroundColor: CustomColor.whiteColor,
            leading: BackButtonWidget(
              onTap: () {
                Get.offAndToNamed(Routes.home);
              },
            ),
          ),
          body: Obx(
            () => controller.isLoading
                ? const CustomLoadingAPI()
                : _bodyWidget(context),
          )),
    );
  }

  StatelessWidget _bodyWidget(BuildContext context) {
    var data = controller.status.value;
    return data == 2
        ? const StatusDataWidget(
            text: Strings.pending,
            icon: Icons.hourglass_empty,
          )
        : data == 1
            ? const StatusDataWidget(
                text: Strings.accepted,
                icon: Icons.check_circle_outline,
              )
            : SingleChildScrollView(
                child: Column(
                  children: [
                    _kycInfoWidget(context),
                    _buttonWidget(context),
                  ],
                ),
              );
  }

  Column _kycInfoWidget(context) {
    return Column(
      children: [
        KycHeadingWidget(),
        verticalSpace(Dimensions.paddingSize),
        const KycInputWidget(),
      ],
    );
  }

  SingleChildRenderObjectWidget _buttonWidget(context) {
    if (controller.status.value == 0 || controller.status.value == 3) {
      return Padding(
          padding: EdgeInsets.only(
            left: Dimensions.paddingSize,
            right: Dimensions.paddingSize,
          ),
          child: Obx(
            () => controller.isSubmitLoading
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: Strings.submit,
                    fontWeight: FontWeight.w600,
                    isLoading: controller.isSubmitLoading,
                    onPressed: () {
                      controller.kycSubmitProcess();
                    },
                    buttonTextColor: CustomColor.whiteColor,
                    buttonColor: CustomColor.primaryLightColor,
                    elevation: 0,
                    borderColor: Theme.of(context).primaryColor,
                    borderWidth: 1.5,
                    radius: Dimensions.radius * 1.2,
                    height: Dimensions.heightSize * 4.67,
                  ),
          ));
    } else {
      return const SizedBox.shrink();
    }
  }
}
