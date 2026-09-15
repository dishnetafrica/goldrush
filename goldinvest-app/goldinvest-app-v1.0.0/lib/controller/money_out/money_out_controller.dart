import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:goldinvest/extensions/extensions.dart';

import '../../backend/model/common/common_success_model.dart';
import '../../backend/model/money_out/dynamic_money_out.dart';
import '../../backend/model/money_out/money_out_wallet_and_gateways.dart';
import '../../backend/model/money_out/money_out_wallet_and_gateways.dart'
    as balanceTypes;
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';
import '../../routes/routes.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/inputs/primary_input_widget.dart';
import '../../widgets/common/inputs/withdraw_image_picker.dart';
import '../increment_decrement/amount_controller_money_out.dart';

class MoneyOutController extends GetxController {
  final AmountControllerMoneyOut amountController =
      Get.put(AmountControllerMoneyOut());
  RxString selectCurrency = ''.obs;
  RxString selectBalanceType = ''.obs;
  RxString selectBalanceValue = ''.obs;

  RxString selectPaymentGateway = ''.obs;

  RxDouble exchangeRate = 0.0.obs;
  RxDouble minLimit = 0.0.obs;
  RxDouble maxLimit = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxDouble availableBalance = 0.0.obs;
  // RxDouble profitBalance = 0.0.obs;

  RxString currencyCode = "".obs;
  RxDouble requestAmount = 0.0.obs;
  RxDouble totalFees = 0.0.obs;
  RxDouble totalPayable = 0.0.obs;
  RxDouble willGetAmount = 0.0.obs;

  List<String> listImagePath = [];
  List<String> listFieldName = [];
  List<TextEditingController> inputFieldControllers = [];
  RxBool hasFile = false.obs;

  RxList inputFields = [].obs;

  final moneyOutAmount = TextEditingController();
  List<balanceTypes.Type> sendingBalanceTypeList = [];
  List<PaymentGateway> sendingGatewayList = [];

  @override
  void onInit() {
    balanceTypeInfoProcess();
    gatewayInfoProcess();

    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late MoneyOutWalletAndGatewaysModel _moneyOutWalletAndGatewaysModel;
  MoneyOutWalletAndGatewaysModel get moneyOutWalletAndGatewaysModel =>
      _moneyOutWalletAndGatewaysModel;

  Future<MoneyOutWalletAndGatewaysModel?> balanceTypeInfoProcess() async {
    return RequestProcess().request<MoneyOutWalletAndGatewaysModel>(
      showResult: true,
      fromJson: MoneyOutWalletAndGatewaysModel.fromJson,
      apiEndpoint: ApiEndpoint.moneyOut,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _moneyOutWalletAndGatewaysModel = value!;

        for (var balanceType
            in _moneyOutWalletAndGatewaysModel.data.balanceType.types) {
          sendingBalanceTypeList.add(balanceTypes.Type(
            name: balanceType.name,
            value: balanceType.value,
          ));
        }
      },
    );
  }

  Future<MoneyOutWalletAndGatewaysModel?> gatewayInfoProcess() async {
    return RequestProcess().request<MoneyOutWalletAndGatewaysModel>(
      showResult: true,
      fromJson: MoneyOutWalletAndGatewaysModel.fromJson,
      apiEndpoint: ApiEndpoint.moneyOut,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _moneyOutWalletAndGatewaysModel = value!;

        for (var gateways
            in _moneyOutWalletAndGatewaysModel.data.paymentGateways) {
          sendingGatewayList.add(PaymentGateway(
              alias: gateways.alias,
              currencies: gateways.currencies,
              desc: gateways.desc,
              id: gateways.id,
              name: gateways.name,
              status: gateways.status,
              type: gateways.type));
        }
      },
    );
  }

  double getFees() {
    requestAmount.value = double.parse(
        amountController.amountController.text.isEmpty
            ? "0.0"
            : amountController.amountController.text);

    totalFees.value = ((((requestAmount.value * exchangeRate.value) / 100) *
                percentCharge.value) /
            exchangeRate.value) +
        (fixedCharge.value / exchangeRate.value);

    return totalFees.value;
  }

  double getTotalPayable() {
    totalPayable.value = requestAmount.value + totalFees.value;
    return totalPayable.value;
  }

  double willGet() {
    willGetAmount.value = requestAmount.value * exchangeRate.value;
    return willGetAmount.value;
  }

  final _isInsertLoading = false.obs;
  bool get isInsertLoading => _isInsertLoading.value;

  late DynamicInputWithdrawModel _moneyOutInsertModel;
  DynamicInputWithdrawModel get moneyOutInsertModel => _moneyOutInsertModel;

  Future<DynamicInputWithdrawModel?> manualPaymentGetGatewaysProcess() async {
    inputFields.clear();
    listImagePath.clear();
    listFieldName.clear();
    inputFieldControllers.clear();
    update();

    return RequestProcess().request<DynamicInputWithdrawModel>(
        fromJson: DynamicInputWithdrawModel.fromJson,
        apiEndpoint: ApiEndpoint.moneyOutInputField,
        method: HttpMethod.GET,
        isLoading: _isInsertLoading,
        showErrorMessage: true,
        queryParams: {'currency': selectCurrency.value},
        onSuccess: (value) {
          _moneyOutInsertModel = value!;

          final data = _moneyOutInsertModel.data.inputFields;

          for (int item = 0; item < data.length; item++) {
            var textEditingController = TextEditingController();
            inputFieldControllers.add(textEditingController);

            if (data[item].type.contains('file')) {
              hasFile.value = true;
              inputFields.add(
                Padding(
                  padding: const EdgeInsets.only(bottom: 8.0),
                  child: WithdrawManualPaymentImageWidget(
                    labelName: data[item].label,
                    fieldName: data[item].name,
                  ),
                ),
              );
            } else if (data[item].type.contains('text') ||
                data[item].type.contains('textarea')) {
              inputFields.add(
                Column(
                  children: [
                    PrimaryInputWidget(
                      showBorderSide: true,
                      textController: inputFieldControllers[item],
                      hintText: data[item].label,
                      isValidator: data[item].required,
                      fillColor: Theme.of(Get.context!).colorScheme.surface,
                      inputFormatters: [
                        LengthLimitingTextInputFormatter(
                          int.parse(data[item].validation.max.toString()),
                        ),
                      ],
                    ).paddingOnly(bottom: Dimensions.marginSizeVertical * 0.75),
                  ],
                ),
              );
            }
          }

          Routes.moneyOutManualPaymentScreen.toNamed;
          update();
        });
  }

  final _isConfirmLoading = false.obs;
  bool get isConfirmLoading => _isConfirmLoading.value;

  late CommonSuccessModel _manualPaymentConfirmModel;
  CommonSuccessModel get manualPaymentConfirmModel =>
      _manualPaymentConfirmModel;

  Future<CommonSuccessModel?> confirmProcess() async {
    _isConfirmLoading.value = true;
    Map<String, String> inputBody = {
      'amount': amountController.amountController.text,
      'payment_gateway': selectPaymentGateway.value,
      'wallet_type': selectBalanceValue.value,
    };

    final data = moneyOutInsertModel.data.inputFields;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;
      }
    }

    return RequestProcess().request<CommonSuccessModel>(
        fromJson: CommonSuccessModel.fromJson,
        apiEndpoint: ApiEndpoint.moneyOutSubmit,
        isLoading: _isConfirmLoading,
        method: HttpMethod.POST,
        body: inputBody,
        showErrorMessage: true,
        isBasic: false,
        onSuccess: (value) {
          _manualPaymentConfirmModel = value!;
          Routes.moneyOutCongratulation.toNamed;
        });
  }
}
