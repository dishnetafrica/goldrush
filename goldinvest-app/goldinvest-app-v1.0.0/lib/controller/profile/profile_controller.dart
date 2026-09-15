import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:goldinvest/backend/model/common/common_success_model.dart';
import 'package:image_picker/image_picker.dart';

import '../../backend/model/profile/profile_info_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/custom_snackbar.dart';
import '../../backend/utils/request_process.dart';
import '../../routes/routes.dart';
import '../delivery_address/delivery_address_controller.dart';

class ProfileController extends GetxController {
  final deliveryAddressController = Get.put(DeliveryAddressController());
  RxString selectState = ''.obs;
  RxString selectCity = ''.obs;
  RxString selectCountry = ''.obs;
  RxString phoneCode = ''.obs;
  RxString userImage = ''.obs;
  final firstNameController = TextEditingController();
  final lastNameController = TextEditingController();
  final phoneNumberController = TextEditingController();
  final zipCodeController = TextEditingController();
  final addressController = TextEditingController();

  RxString userFullName = ''.obs;
  RxString userLevel = ''.obs;
  static final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;
  late ProfileInfoModel _profileInfoModel;
  ProfileInfoModel get profileInfoModel => _profileInfoModel;

  static final _isProfileDeleteLoading = false.obs;
  bool get isProfileDeleteLoading => _isProfileDeleteLoading.value;

  static final _isProfileUpdateLoading = false.obs;
  bool get isProfileUpdateLoading => _isProfileUpdateLoading.value;

  Future<ProfileInfoModel?> get onProfileInfo => profileInfoProcess();
  @override
  void onInit() {
    profileInfoProcess();

    super.onInit();
  }

  Future<ProfileInfoModel?> profileInfoProcess() async {
    return RequestProcess().request<ProfileInfoModel>(
      showResult: true,
      fromJson: ProfileInfoModel.fromJson,
      apiEndpoint: ApiEndpoint.profileInfo,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _profileInfoModel = value!;
        _setData(_profileInfoModel);
      },
    );
  }

  void _setData(ProfileInfoModel profileModel) {
    var userInfo = _profileInfoModel.data.userInfo;
    var imagePaths = _profileInfoModel.data.imagePaths;
    var data = _profileInfoModel.data;
    userFullName.value = '${data.userInfo.firstname} ${data.userInfo.lastname}';
    userLevel.value = data.userInfo.level;

    firstNameController.text = data.userInfo.firstname;
    lastNameController.text = data.userInfo.lastname;
    phoneNumberController.text = data.userInfo.mobile!;

    zipCodeController.text = data.userInfo.postalCode;
    addressController.text = data.userInfo.address;
    if (userInfo.image != '') {
      userImage.value =
          "${imagePaths.baseUrl}/${imagePaths.pathLocation}/${userInfo.image}";
    } else {
      userImage.value = "${imagePaths.baseUrl}/${imagePaths.defaultImage}";
    }
  }

  static late CommonSuccessModel _checkOutSuccessModel;
  CommonSuccessModel get checkoutSuccessModel => _checkOutSuccessModel;

  Future<CommonSuccessModel?> onProfileUpdateInfo() async {
    Map<String, dynamic> inputBody = {
      'firstname': firstNameController.text,
      'lastname': lastNameController.text,
      'mobile_code': phoneCode.value,
      'mobile': phoneNumberController.text,
      'country': selectCountry.value,
      'city': selectCity.value,
      'state': selectState.value,
      'postal_code': zipCodeController.text,
      'address': addressController.text,
    };
    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.profileUpdate,
      isLoading: _isProfileUpdateLoading,
      method: HttpMethod.POST,
      body: inputBody,
      isBasic: false,
      fieldList: isImagePathSet.value ? ['image'] : null,
      pathList: isImagePathSet.value ? [imagePath.value] : null,
      onSuccess: (value) {
        _checkOutSuccessModel = value!;
      },
    );
  }

  @override
  void onClose() {
    firstNameController.dispose();
    lastNameController.dispose();
    zipCodeController.dispose();
    phoneNumberController.dispose();
    addressController.dispose();
    super.onClose();
  }

  File? pickedFile;
  ImagePicker imagePicker = ImagePicker();
  var isImagePathSet = false.obs;
  var imagePath = "".obs;

  void setImagePath(String path) {
    imagePath.value = path;
    isImagePathSet.value = true;
  }

  Future pickImage(imageSource) async {
    try {
      final image = await ImagePicker().pickImage(
        source: imageSource,
        imageQuality: 40,
        maxHeight: 600,
        maxWidth: 600,
      );
      if (image == null) return;

      pickedFile = File(image.path);
      setImagePath(pickedFile!.path);
    } on PlatformException catch (e) {
      CustomSnackBar.error('Error: $e');
    }
  }

  static late CommonSuccessModel _profileDeleteSuccessModel;
  CommonSuccessModel get profileDeleteSuccessModel =>
      _profileDeleteSuccessModel;

  Future<CommonSuccessModel?> onProfileDelete() async {
    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.profileDelete,
      isLoading: _isProfileDeleteLoading,
      method: HttpMethod.POST,
      isBasic: false,
      onSuccess: (value) {
        Get.toNamed(Routes.signInScreen);
      },
    );
  }
}
