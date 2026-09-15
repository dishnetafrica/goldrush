import 'package:get/get.dart';
import 'package:goldinvest/controller/delivery_address/delivery_address_controller.dart';

import '../../backend/model/checkout/checkout_model.dart';
import '../../backend/model/checkout/checkout_success_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';
import '../../views/gold_store/gold_store_congratulation_screen.dart';
import '../gold/gold_store_controller.dart';
import '../profile/profile_controller.dart';

class CheckoutController extends GetxController {
  final GoldStoreController goldStoreController =
      Get.put(GoldStoreController());
  final DeliveryAddressController deliveryAddressController =
      Get.put(DeliveryAddressController());
  final ProfileController profileController = Get.put(ProfileController());

  RxDouble count = 1.0.obs;
  RxString goldName = "".obs;
  RxDouble goldPrice = 0.0.obs;
  RxString goldImage = "".obs;
  RxDouble charge = 0.0.obs;
  RxDouble totalPayable = 0.0.obs;

  RxString userWallet = "".obs;
  RxString availableBalance = "".obs;
  RxString selectedCardTitle = ''.obs;

  RxString cashOnDelivery = "".obs;

//Congratulation
  RxString itemName = "".obs;
  RxDouble itemTotalAmount = 0.0.obs;
  RxString itemPaymentType = "".obs;
  RxDouble itemTotalPayable = 0.0.obs;

  List<CheckOutModel> sendingCheckOutInfo = [];
  late CheckOutModel _checkOutModel;
  CheckOutModel get checkOutModel => _checkOutModel;

  @override
  void onInit() {
    checkOutInfoProcess();

    if (userWallet.isNotEmpty) {
      selectCard(0, userWallet.value);
    } else if (cashOnDelivery.isNotEmpty) {
      selectCard(1, cashOnDelivery.value);
    } else {
      selectCard(-1, '');
    }

    super.onInit();
  }

  RxInt selectedCardIndex = (-1).obs;

  void selectCard(int index, String title) {
    selectedCardIndex.value = index;
    selectedCardTitle.value = title;
  }

  void increment() {
    count.value += 1;

    calculateTotalPayable();
  }

  void decrement() {
    if (count.value > 1) {
      count.value -= 1;

      calculateTotalPayable();
    }
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  Future<CheckOutModel?> checkOutInfoProcess() async {
    return RequestProcess().request<CheckOutModel>(
      showResult: true,
      fromJson: CheckOutModel.fromJson,
      apiEndpoint: ApiEndpoint.goldCheckOut,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      queryParams: {'slug': goldStoreController.selectSlug.value},
      showSuccessMessage: false,
      onSuccess: (value) {
        if (value != null) {
          _checkOutModel = value;
          _setData(_checkOutModel);
        }
      },
    );
  }

  void _setData(CheckOutModel checkOutModel) {
    var data = checkOutModel.data;
    var deliveryType = checkOutModel.data.paymentType;
    goldName.value = data.gold.title;
    goldPrice.value = (data.gold.price).toDouble();
    goldImage.value = "${data.baseUrl}/${data.imagePath}/${data.gold.image}";
    charge.value = (data.gold.charge).toDouble();

    userWallet.value = deliveryType.wallet;
    cashOnDelivery.value = deliveryType.cashOnDelivery;
    availableBalance.value = data.availableBalance;
  }

  double calculateTotalPayable() {
    totalPayable.value = (count.value * goldPrice.value) + charge.value;
    return totalPayable.value;
  }

  static late CheckoutSuccessModel _checkOutSuccessModel;
  CheckoutSuccessModel get checkoutSuccessModel => _checkOutSuccessModel;

  final _isLoadingCheckout = false.obs;
  bool get isLoadingCheckOut => _isLoadingCheckout.value;

  Future<CheckoutSuccessModel?> onCheckOutInfo() async {
    Map<String, dynamic> inputBody = {
      'qtybutton': count.value.toInt(),
      'payment_type': selectedCardTitle.value,
      'country': profileController.selectCountry.value,
      'phone_code': profileController.phoneCode.value.isNotEmpty
          ? profileController.phoneCode.value
          : deliveryAddressController.phoneCode.value,
      'phone': profileController.phoneNumberController.text,
      'state': profileController.selectState.value,
      'city': profileController.selectCity.value,
      'zip_code': profileController.zipCodeController.text,
      'address': profileController.addressController.text,
      'slug': goldStoreController.selectSlug.value,
      'lang': 'en',
    };
    return RequestProcess().request<CheckoutSuccessModel>(
      fromJson: CheckoutSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.goldSubmit,
      isLoading: _isLoadingCheckout,
      method: HttpMethod.POST,
      body: inputBody,
      showErrorMessage: true,
      isBasic: false,
      onSuccess: (value) {
        _checkOutSuccessModel = value!;
        var data = checkoutSuccessModel.data;
        itemName.value = data.item;
        itemTotalAmount.value = data.price;
        itemPaymentType.value = data.paymentType;
        itemTotalPayable.value = data.totalAmount;

        Get.offAll(GoldStoreCongratulationMobileScreen(
          checkOutSuccessModel: _checkOutSuccessModel,
        ));
      },
    );
  }
}
