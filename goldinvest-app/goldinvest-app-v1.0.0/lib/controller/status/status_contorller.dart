import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/model/my_status/my_status_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class MyStatusController extends GetxController {
  RxInt totalRefers = 0.obs;
  RxInt totalInvested = 0.obs;
  final referCode = TextEditingController();
  final referLink = TextEditingController();
  RxString referUserName = "".obs;
  RxString userReferCode = "".obs;
  RxString referUserImage = "".obs;

  List<Datum> referralUsers = [];

  @override
  void onInit() {
    myStatusInfoProcess();

    super.onInit();
  }

  @override
  void onClose() {
    referCode.dispose();
    referLink.dispose();
    super.onClose();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late MyStatusModel _myStatusModel;
  MyStatusModel get profileInfoModel => _myStatusModel;

  Future<MyStatusModel?> myStatusInfoProcess() async {
    return RequestProcess().request<MyStatusModel>(
      showResult: true,
      fromJson: MyStatusModel.fromJson,
      apiEndpoint: ApiEndpoint.myStatus,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _myStatusModel = value!;
        _setData(_myStatusModel);
      },
    );
  }

  void _setData(MyStatusModel myStatusModel) {
    var data = _myStatusModel.data;

    totalRefers.value = data.totalRefers;
    totalInvested.value = data.totalInvestment;
    referCode.text = data.referCode;
    referLink.text = data.referLink;

    referralUsers = data.referUsers.data!;
  }
}
