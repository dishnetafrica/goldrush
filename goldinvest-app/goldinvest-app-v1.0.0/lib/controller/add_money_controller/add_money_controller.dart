import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/add_money/add_money_info_model.dart'
    as c;
import 'package:goldinvest/backend/model/add_money/add_money_info_model.dart';
import 'package:goldinvest/extensions/extensions.dart';
import 'package:goldinvest/views/add_money/tatum_payment_screen.dart';

import '../../backend/model/add_money/add_money_gateway_model.dart';
import '../../backend/model/add_money/add_money_manual_gateway_model.dart';
import '../../backend/model/add_money/tatum_gateway_model.dart';
import '../../backend/model/common/common_success_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/services/tatum_gateway/tatum_gateway.dart';
import '../../backend/utils/api_method.dart';
import '../../backend/utils/request_process.dart';
import '../../routes/routes.dart';
import '../../utils/dimensions.dart';
import '../../widgets/common/inputs/primary_input_widget.dart';
import '../../widgets/common/inputs/withdraw_image_picker.dart';
import '../../widgets/web_view_widget/web_payment_screen.dart';
import '../increment_decrement/amount_controller_add_money.dart';

class AddMoneyController extends GetxController {
  final AmountControllerAddMoney amountController =
      Get.put(AmountControllerAddMoney());

  RxString selectCurrency = ''.obs;
  RxString selectPaymentGateway = ''.obs;
  RxString selectPaymentGatewayType = ''.obs;
  RxString selectPaymentGatewayName = ''.obs;

  RxDouble gatewayRate = 0.0.obs;

  RxDouble exchangeRate = 0.0.obs;
  RxDouble minLimit = 0.0.obs;
  RxDouble maxLimit = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble percentCharge = 0.0.obs;
  RxString availableBalance = "".obs;
  RxString currencyCode = " ".obs;
  RxDouble requestAmount = 0.0.obs;
  RxDouble totalFees = 0.0.obs;
  RxDouble totalPayable = 0.0.obs;
  RxBool hasFile = false.obs;
  RxList inputFields = [].obs;
  RxString qrAddress = ''.obs;
  String payableAmount = "";
  RxDouble maximumLimit = 0.0.obs;
  RxDouble minimumLimit = 0.0.obs;

  List<String> listImagePath = [];
  List<String> listFieldName = [];
  List<PaymentGateway> sendingGatewayList = [];
  List<c.Currency> sendingCurrencyList = [];

  List<TextEditingController> inputFieldControllers = [];
  @override
  void onInit() {
    gatewayInfoProcess();

    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late AddMoneyPaymentGatewayModel _addMoneyPaymentGatewayModel;
  AddMoneyPaymentGatewayModel get addMoneyPaymentGatewayModel =>
      _addMoneyPaymentGatewayModel;

  Future<AddMoneyPaymentGatewayModel?> gatewayInfoProcess() async {
    return RequestProcess().request<AddMoneyPaymentGatewayModel>(
      showResult: true,
      fromJson: AddMoneyPaymentGatewayModel.fromJson,
      apiEndpoint: ApiEndpoint.addMoneyManualGateways,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _addMoneyPaymentGatewayModel = value!;

        availableBalance.value =
            _addMoneyPaymentGatewayModel.data.availableBalance;

        for (var gateways
            in _addMoneyPaymentGatewayModel.data.paymentGateways) {
          for (var currency in gateways.currencies) {
            sendingCurrencyList.add(
              c.Currency(
                  type: gateways.type,
                  id: currency.id,
                  paymentGatewayId: currency.paymentGatewayId,
                  name: currency.name,
                  alias: currency.alias,
                  currencyCode: currency.currencyCode,
                  currencySymbol: currency.currencySymbol,
                  minLimit: currency.minLimit,
                  maxLimit: currency.maxLimit,
                  percentCharge: currency.percentCharge,
                  fixedCharge: currency.fixedCharge,
                  rate: currency.rate,
                  createdAt: currency.createdAt,
                  updatedAt: currency.updatedAt,
                  image: currency.image,
                  selectPaymentGatewayName: currency.selectPaymentGatewayName),
            );
          }
        }
      },
    );
  }

  void processPayment() {
    if (selectPaymentGatewayType.value == "AUTOMATIC") {
      if (selectCurrency.value.contains('tatum')) {
        tatumProcess();
      } else {
        addMoneyConfirm();
      }
    } else {
      manualPaymentGetGatewaysProcess();
    }
  }

  double getFees() {
    requestAmount.value = double.parse(
        amountController.amountController.text.isEmpty
            ? "0.0"
            : amountController.amountController.text);
    totalFees.value =
        (((requestAmount.value / 1000) * (percentCharge.value * 10)) *
                gatewayRate.value) +
            fixedCharge.value;
    return totalFees.value;
  }

  double getTotalPayable() {
    totalPayable.value =
        (requestAmount.value * gatewayRate.value) + totalFees.value;
    return totalPayable.value;
  }

  final _isInsertLoading = false.obs;
  bool get isInsertLoading => _isInsertLoading.value;

  final _isWebViewLoading = false.obs;
  bool get isWebViewLoading => _isWebViewLoading.value;

  late AddMoneyGatewayModel _addMoneyGatewayModel;
  AddMoneyGatewayModel get addMoneyGatewayModel => _addMoneyGatewayModel;

  // automatic

  Future<AddMoneyGatewayModel?> addMoneyConfirm() async {
    Map<String, dynamic> inputBody = {
      'amount': double.parse(amountController.amountController.text.isEmpty
          ? "0.0"
          : amountController.amountController.text),
      'currency': selectCurrency.value
    };

    return RequestProcess().request<AddMoneyGatewayModel>(
      fromJson: AddMoneyGatewayModel.fromJson,
      apiEndpoint: ApiEndpoint.addMoneySubmitAutomatic,
      isLoading: _isWebViewLoading,
      method: HttpMethod.POST,
      body: inputBody,
      showErrorMessage: true,
      onSuccess: (value) {
        _addMoneyGatewayModel = value!;
        Get.to(() => WebPaymentScreen());
      },
    );
  }

  late DynamicInputAddMoneyModel _addMoneyInsertModel;
  DynamicInputAddMoneyModel get moneyOutInsertModel => _addMoneyInsertModel;

  // manual

  Future<DynamicInputAddMoneyModel?> manualPaymentGetGatewaysProcess() async {
    inputFields.clear();
    listImagePath.clear();
    listFieldName.clear();
    inputFieldControllers.clear();
    update();

    return RequestProcess().request<DynamicInputAddMoneyModel>(
        fromJson: DynamicInputAddMoneyModel.fromJson,
        apiEndpoint: ApiEndpoint.addMoneyAdditionalField,
        method: HttpMethod.GET,
        isLoading: _isInsertLoading,
        showErrorMessage: true,
        queryParams: {'alias': selectCurrency.value},
        onSuccess: (value) {
          _addMoneyInsertModel = value!;

          final data = _addMoneyInsertModel.data.inputFields;

          for (int item = 0; item < data.length; item++) {
            // make the dynamic controller
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

          Routes.addMoneyManualPaymentScreen.toNamed;
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
      'currency': selectCurrency.value,
    };

    final data = moneyOutInsertModel.data.inputFields;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;
      }
    }

    return RequestProcess().request<CommonSuccessModel>(
        fromJson: CommonSuccessModel.fromJson,
        apiEndpoint: ApiEndpoint.addMoneySubmit,
        isLoading: _isConfirmLoading,
        method: HttpMethod.POST,
        body: inputBody,
        isBasic: false,
        onSuccess: (value) {
          _manualPaymentConfirmModel = value!;
          Routes.addMoneyCongratulation.toNamed;
        });
  }

//tatum
  late TatumGatewayModel _tatumGatewayModel;

  TatumGatewayModel get tatumGatewayModel => _tatumGatewayModel;

  late CommonSuccessModel _addMoneyTatumConfirm;
  CommonSuccessModel get addMoneyTatumConfirm => _addMoneyTatumConfirm;

  final _isTatumConfirmLoading = false.obs;
  bool get isTatumConfirmLoading => _isTatumConfirmLoading.value;

  Future<CommonSuccessModel?> tatumConfirmProcess(BuildContext context) async {
    _isTatumConfirmLoading.value = true;
    update();

    Map<String, String> inputBody = {};
    final data = _tatumGatewayModel.data.addressInfo.inputFields;
    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;
      }
    }

    await AddMoneyApiServices.tatumConfirmApiProcess(
      body: inputBody,
      url: _tatumGatewayModel.data.addressInfo.submitUrl,
    ).then((value) {
      _addMoneyTatumConfirm = value!;
      Get.toNamed(Routes.addMoneyCongratulation);

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isTatumConfirmLoading.value = false;
    update();
    return addMoneyTatumConfirm;
  }

  final _isTatumProcessLoading = false.obs;
  bool get isTatumProcessLoading => _isTatumProcessLoading.value;

  Future<TatumGatewayModel?> tatumProcess() async {
    inputFields.clear();
    inputFieldControllers.clear();

    update();

    Map<String, dynamic> inputBody = {
      'amount': double.parse(amountController.amountController.text.isEmpty
          ? "0.0"
          : amountController.amountController.text),
      'currency': selectCurrency.value
    };
    return RequestProcess().request<TatumGatewayModel>(
        fromJson: TatumGatewayModel.fromJson,
        apiEndpoint: ApiEndpoint.addMoneySubmitAutomatic,
        isLoading: _isTatumProcessLoading,
        method: HttpMethod.POST,
        body: inputBody,
        showErrorMessage: true,
        onSuccess: (value) {
          _tatumGatewayModel = value!;
          final data = _tatumGatewayModel.data.addressInfo.inputFields;
          qrAddress.value = _tatumGatewayModel.data.addressInfo.address;

          for (int item = 0; item < data.length; item++) {
            // make the dynamic controller
            var textEditingController = TextEditingController();
            inputFieldControllers.add(textEditingController);

            if (data[item].type.contains('text')) {
              inputFields.add(
                Column(
                  children: [
                    PrimaryInputWidget(
                      textController: inputFieldControllers[item],
                      hintText: data[item].label,
                      validator: data[item].required,
                      isFilled: false,
                      showBorderSide: true,
                      fillColor: Colors.transparent,
                      shadowColor: Colors.transparent,
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

          Get.to(() => TatumPaymentScreen());
        });
  }
}
