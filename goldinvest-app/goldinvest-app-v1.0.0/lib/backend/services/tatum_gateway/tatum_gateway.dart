import '../../model/common/common_success_model.dart';
import '../../utils/api_method.dart';
import '../../utils/custom_snackbar.dart';

class AddMoneyApiServices {
  static Future<CommonSuccessModel?> tatumConfirmApiProcess(
      {required Map<String, dynamic> body, required String url}) async {
    Map<String, dynamic>? mapResponse;
    try {
      mapResponse = await ApiMethod(isBasic: false)
          .post(url, body, code: 200, showErrorMessage: true);
      if (mapResponse != null) {
        CommonSuccessModel result = CommonSuccessModel.fromJson(mapResponse);
        CustomSnackBar.success(result.message.success.first.toString());
        return result;
      }
    } catch (e) {
      log.e('err from Tatum confirm  api service ==> $e');
      // CustomSnackBar.error('Something went Wrong!');
      return null;
    }
    return null;
  }
}
