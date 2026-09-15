import 'package:get/get.dart';

import '../../backend/model/basic_settings/basic_serivices_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class BasicServices {
  static final List<OnboardScreen> onboardScreen = [];
  static RxString splashImage = ''.obs;
  static RxString appBasicLogoWhite = ''.obs;
  static RxString appBasicLogoDark = ''.obs;
  static RxString privacyPolicy = ''.obs;
  static RxString contactUs = ''.obs;
  static RxString aboutUs = ''.obs;
  static RxString basePath = ''.obs;
  static RxString appImageBasePath = ''.obs;
  static RxString appImagepathLocation = ''.obs;
  static RxString pathLocation = ''.obs;
  static RxString appLogo = ''.obs;
  static RxInt referralEnable = 0.obs;
  static RxInt registrationEnable = 0.obs;
  static RxInt securePasswordEnable = 0.obs;
  static RxInt agreePolicyEnable = 0.obs;

  static final _isLoading = false.obs;
  static bool get isLoading => _isLoading.value;
  static late BasicSettingsModel _basicSettingsModel;
  static BasicSettingsModel get basicSettingsModel => _basicSettingsModel;
  void onInit() {}

  static Future<BasicSettingsModel?> getBasicSettingsInfo() async {
    return RequestProcess().request<BasicSettingsModel>(
      fromJson: BasicSettingsModel.fromJson,
      apiEndpoint: ApiEndpoint.basicSettings,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _basicSettingsModel = value!;
        referralEnable.value =
            _basicSettingsModel.data!.referralSettings!.status!;
        registrationEnable.value =
            _basicSettingsModel.data!.basicSettings!.userRegistration!;
        securePasswordEnable.value =
            _basicSettingsModel.data!.basicSettings!.securePassword!;
        agreePolicyEnable.value =
            _basicSettingsModel.data!.basicSettings!.agreePolicy!;

        basePath.value = _basicSettingsModel.data!.appImagePaths!.baseUrl!;
        appImageBasePath.value =
            _basicSettingsModel.data!.imagePaths!.basePath!;
        appImagepathLocation.value =
            _basicSettingsModel.data!.imagePaths!.pathLocation!;
        pathLocation.value =
            _basicSettingsModel.data!.appImagePaths!.pathLocation!;
        var splash = _basicSettingsModel.data!.splashScreen!.image!;

        // splash
        splashImage.value = "${basePath.value}/$pathLocation/$splash";
        privacyPolicy.value =
            _basicSettingsModel.data!.webLinks!.privacyPolicy!;
        contactUs.value = _basicSettingsModel.data!.webLinks!.contactUs!;
        aboutUs.value = _basicSettingsModel.data!.webLinks!.aboutUs!;

        //app logo
        appLogo.value = _basicSettingsModel.data!.basicSettings!.siteLogoDark!;

        // onboard
        for (var element in _basicSettingsModel.data!.onboardScreens!) {
          onboardScreen.add(
            OnboardScreen(
              title: element.title,
              subTitle: element.subTitle,
              image: element.image,
              status: element.status,
            ),
          );
        }
      },
    );
  }
}
