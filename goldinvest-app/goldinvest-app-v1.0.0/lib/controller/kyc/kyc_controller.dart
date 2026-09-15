import 'package:flutter/material.dart';
import 'package:get/get.dart';
// import 'package:goldinvest/controller/profile/profile_controller.dart';
import 'package:goldinvest/extensions/extensions.dart';
import 'package:goldinvest/routes/routes.dart';

import '../../backend/model/common/common_success_model.dart';
import '../../backend/model/kyc/kyc_info_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';
import '../../widgets/kyc/dynamic_input_field.dart';

class KycInformationController extends GetxController {
  @override
  void onInit() {
    kycInfoProcess();
    emailAddressController.addListener(_updateFormValidity);
    super.onInit();
  }

  List<TextEditingController> inputFieldControllers = [];
  final emailAddressController = TextEditingController();
  RxBool isFormValid = false.obs;
  RxString selectedTransactionItem = ''.obs;
  RxList inputFields = [].obs;
  RxList inputFileFields = [].obs;
  List<String> dropdownList = <String>[].obs;
  RxString selectType = "".obs;
  List<String> listImagePath = [];
  List<String> listFieldName = [];
  RxBool hasFile = false.obs;
  RxInt status = 0.obs;

  @override
  void onClose() {
    emailAddressController.dispose();
    super.onClose();
  }

  void _updateFormValidity() {
    isFormValid.value = emailAddressController.text.isNotEmpty;
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late KycInfoModel _kycInfoModel;
  KycInfoModel get kycInfoModel => _kycInfoModel;

  Future<KycInfoModel?> kycInfoProcess() async {
    inputFields.clear();
    listImagePath.clear();
    listFieldName.clear();
    inputFieldControllers.clear();
    return RequestProcess().request<KycInfoModel>(
      fromJson: KycInfoModel.fromJson,
      apiEndpoint: ApiEndpoint.kycInfo,
      method: HttpMethod.GET,
      isLoading: _isLoading,
      onSuccess: (value) {
        _kycInfoModel = value!;
        var data = _kycInfoModel.data.inputFields;
        status.value = _kycInfoModel.data.status;

        getDynamicInputField(
          data: data,
          inputFieldControllers: inputFieldControllers,
          inputFields: inputFields,
          inputFileFields: inputFileFields,
          hasFile: hasFile,
          selectType: selectType,
        );
      },
    );
  }

  final _isSubmitLoading = false.obs;
  bool get isSubmitLoading => _isSubmitLoading.value;

  late CommonSuccessModel _commonSuccessModel;
  CommonSuccessModel get commonSuccessModel => _commonSuccessModel;

  Future<CommonSuccessModel?> kycSubmitProcess() async {
    Map<String, String> inputBody = {};
    final data = kycInfoModel.data.inputFields;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;
      }
    }

    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.kycSubmit,
      isLoading: _isSubmitLoading,
      method: HttpMethod.POST,
      body: inputBody,
      fieldList: listFieldName,
      pathList: listImagePath,
      onSuccess: (value) {
        inputFields.clear();
        listImagePath.clear();
        listFieldName.clear();
        inputFieldControllers.clear();
        kycInfoProcess();
        Routes.home.offAllNamed;
        _commonSuccessModel = value!;
      },
    );
  }

  void updateImageData(String fieldName, String imagePath) {
    if (listFieldName.contains(fieldName)) {
      int itemIndex = listFieldName.indexOf(fieldName);
      listImagePath[itemIndex] = imagePath;
    } else {
      listFieldName.add(fieldName);
      listImagePath.add(imagePath);
    }
    update();
  }

  String? getImagePath(String fieldName) {
    if (listFieldName.contains(fieldName)) {
      int itemIndex = listFieldName.indexOf(fieldName);
      return listImagePath[itemIndex];
    }
    return null;
  }
}
