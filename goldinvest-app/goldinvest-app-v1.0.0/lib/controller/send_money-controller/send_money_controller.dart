import 'package:flutter/widgets.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/common/common_success_model.dart';
import 'package:goldinvest/extensions/extensions.dart';

import '../../backend/model/send_money/send_money_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';
import '../../routes/routes.dart';
import '../increment_decrement/amount_controller_send_money.dart';

class SendMoneyController extends GetxController {
  final AmountControllerSendMoney amountController =
      Get.put(AmountControllerSendMoney());
  RxDouble exchangeRate = 0.0.obs;
  RxDouble minLimit = 0.0.obs;
  RxDouble maxLimit = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble availableBalance = 0.0.obs;

  RxDouble sendMoneyAmount = 0.0.obs;
  RxDouble totalFees = 0.0.obs;
  RxDouble totalPayable = 0.0.obs;
  final receiverEmail = TextEditingController();

  RxString currencyCode = "".obs;

  @override
  void onInit() {
    sendMoneyInfoProcess();

    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late SendMoneyModel _sendMoneyModel;
  SendMoneyModel get sendMoneyModel => _sendMoneyModel;

  Future<SendMoneyModel?> sendMoneyInfoProcess() async {
    return RequestProcess().request<SendMoneyModel>(
      showResult: true,
      fromJson: SendMoneyModel.fromJson,
      apiEndpoint: ApiEndpoint.sendMoneyWallet,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _sendMoneyModel = value!;
        _setGatewayInfo();

        availableBalance.value = double.parse(
            _sendMoneyModel.data.userWallets.first.balance.toString());
        minLimit.value = _sendMoneyModel.data.charges.minLimit;
        maxLimit.value = _sendMoneyModel.data.charges.maxLimit;
        fixedCharge.value = _sendMoneyModel.data.charges.fixedCharge;
        percentCharge.value = _sendMoneyModel.data.charges.percentCharge;
      },
    );
  }

  late CommonSuccessModel _commonSuccessModel;
  CommonSuccessModel get addMoneyInfoModel => _commonSuccessModel;
  final _isLoadingOnSendMoney = false.obs;
  bool get isLoadingOnSendMoney => _isLoadingOnSendMoney.value;

  Future<CommonSuccessModel?> onSendMoney() async {
    Map<String, dynamic> inputBody = {
      'sender_amount': double.parse(
          amountController.amountController.text.isEmpty
              ? "0.0"
              : amountController.amountController.text),
      'receiver': receiverEmail.text,
    };
    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.sendMoney,
      isLoading: _isLoadingOnSendMoney,
      method: HttpMethod.POST,
      body: inputBody,
      showErrorMessage: true,
      onSuccess: (value) {
        _commonSuccessModel = value!;

        Routes.sendMoneyCongratulationScreen.toNamed;
      },
    );
  }

  void _setGatewayInfo() {
    var wallet = _sendMoneyModel.data.charges;

    percentCharge.value = double.parse(wallet.percentCharge.toString());
    fixedCharge.value = double.parse(wallet.fixedCharge.toString());
  }

  RxDouble getFees() {
    sendMoneyAmount.value = double.parse(
        amountController.amountController.text.isEmpty
            ? "0.0"
            : amountController.amountController.text);

    totalFees.value = ((sendMoneyAmount.value / 100) * percentCharge.value) +
        fixedCharge.value;

    return totalFees;
  }

  RxDouble getTotalPayable() {
    totalPayable.value = sendMoneyAmount.value + totalFees.value;
    return totalPayable;
  }
}
