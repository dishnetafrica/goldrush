import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/extensions/extensions.dart';
import 'package:pin_code_fields/pin_code_fields.dart';
import '../../../routes/routes.dart';
import '../../backend/model/common/common_success_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class TwoFaSecurityVerificationController extends GetxController {
  final pinCodeController = TextEditingController();
  StreamController<ErrorAnimationType>? errorController;
  RxString userToken = ''.obs;
  var currentText = ''.obs;

  Future<CommonSuccessModel?> get onSubmit => twoFaOtpVerifyProcess();

  void changeCurrentText(value) {
    currentText.value = value;
  }

  @override
  void dispose() {
    pinCodeController.dispose();
    super.dispose();
  }

  @override
  void onInit() {
    errorController = StreamController<ErrorAnimationType>();
    timerInit();
    super.onInit();
  }

  void timerInit() {
    timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (secondsRemaining.value != 0) {
        secondsRemaining.value--;
      } else {
        enableResend.value = true;
      }
    });
  }

  RxInt secondsRemaining = 59.obs;
  RxBool enableResend = false.obs;
  Timer? timer;

  void resendCode() {
    secondsRemaining.value = 59;
    enableResend.value = false;
    timerInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late CommonSuccessModel _commonSuccessModel;
  CommonSuccessModel get twoFaOtpVerificationModel => _commonSuccessModel;

  Future<CommonSuccessModel?> twoFaOtpVerifyProcess() async {
    Map<String, dynamic> inputBody = {
      'code': pinCodeController.text,
    };

    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.twoFaVerify,
      isLoading: _isLoading,
      method: HttpMethod.POST,
      body: inputBody,
      onSuccess: (value) {
        _commonSuccessModel = value!;

        Routes.home.toNamed;
      },
    );
  }
}
