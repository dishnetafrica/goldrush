part of 'checkout_screen.dart';

class CheckoutMobileScreen extends StatelessWidget {
  CheckoutMobileScreen({super.key});
  final CheckoutController checkoutController = Get.put(CheckoutController());
  final DeliveryAddressController deliveryAddressController =
      Get.put(DeliveryAddressController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: PrimaryAppBar(
        backgroundColor: CustomColor.whiteColor,
        Strings.checkout,
        showBackButton: true,
        leading: BackButtonWidget(
          onTap: () => Get.toNamed(Routes.deliveryAddress),
        ),
      ),
      body: Obx(
        () => checkoutController.isLoading
            ? const CustomLoadingAPI()
            : _bodyWidget(context),
      ),
    );
  }

  Padding _bodyWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.all(Dimensions.paddingSize),
      child: SingleChildScrollView(
        child: Column(
          children: [
            _userAddressWidget(context),
            _orderedProductInfoWidget(context),
            _receiptWidget(context),
            _paymentTypeWidget(context),
            _buttonWidget(context)
          ],
        ),
      ),
    );
  }

  UserAddressWidget _userAddressWidget(context) {
    return UserAddressWidget();
  }

  OrderProductInfoWidget _orderedProductInfoWidget(context) {
    return OrderProductInfoWidget();
  }

  ReceptWidget _receiptWidget(context) {
    return ReceptWidget();
  }

  PaymentInfoWidget _paymentTypeWidget(context) {
    return PaymentInfoWidget();
  }

  Padding _buttonWidget(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: Dimensions.marginSizeVertical,
      ),
      child: Column(
        children: [
          Obx(
            () => checkoutController.isLoadingCheckOut
                ? const CustomLoadingAPI()
                : PrimaryButton(
                    title: checkoutController.selectedCardIndex.value == 0
                        ? Strings.payNow
                        : Strings.orderNow,
                    fontWeight: FontWeight.w600,
                    onPressed: () {
                      checkoutController.onCheckOutInfo();
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
