import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/utils/request_process.dart';

import '../../backend/model/common/common_success_model.dart';
import '../../backend/model/two_fa/two_fa_info_model.dart';
import '../../backend/services/api_endpoint.dart';

class TwoFaVerificationController extends GetxController {
  Future<CommonSuccessModel?> get onEnableOrDisable => twoFaSubmitApiProcess();
  RxString qrCode = ''.obs;
  RxString alert = ''.obs;
  RxInt status = 0.obs;
  final qrSecret = TextEditingController();
  final pinCodeController = TextEditingController();

  @override
  void onInit() {
    twoFaGetApiProcess();
    super.onInit();
  }

  final _isLoading = false.obs;
  late TwoFaInfoModel _twoFaInfoModel;

  bool get isLoading => _isLoading.value;
  TwoFaInfoModel get twoFaInfoModel => _twoFaInfoModel;

  Future<TwoFaInfoModel?> twoFaGetApiProcess() async {
    return RequestProcess().request<TwoFaInfoModel>(
      fromJson: TwoFaInfoModel.fromJson,
      apiEndpoint: ApiEndpoint.twoFaStatus,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      onSuccess: (value) {
        _twoFaInfoModel = value!;
        _setData(_twoFaInfoModel);
      },
    );
  }

  void _setData(TwoFaInfoModel twoFaInfoModel) {
    qrCode.value = twoFaInfoModel.data.qrCode;
    status.value = twoFaInfoModel.data.status;
    qrSecret.text = twoFaInfoModel.data.qrSecret;
  }

  /// >> set loading process & Two Fa Submit Model
  final _isSubmitLoading = false.obs;
  bool get isSubmitLoading => _isSubmitLoading.value;

  /// >> get loading process & Two Fa Submit Model
  late CommonSuccessModel _twoFaSubmitModel;
  CommonSuccessModel get twoFaSubmitModel => _twoFaSubmitModel;

  ///* Two fa submit api process
  Future<CommonSuccessModel?> twoFaSubmitApiProcess() async {
    Map<String, dynamic> inputBody = {
      'status': status.value == 0 ? '1' : '0',
    };

    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.twoFaStatusVerify,
      isLoading: _isSubmitLoading,
      method: HttpMethod.POST,
      body: inputBody,
      onSuccess: (value) {
        _twoFaSubmitModel = value!;
        status.value = status.value == 0 ? 1 : 0;
      },
    );
  }
}
