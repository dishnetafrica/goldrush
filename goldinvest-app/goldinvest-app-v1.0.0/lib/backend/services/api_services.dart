// ignore: depend_on_referenced_packages
import 'package:http/http.dart' as http;

import '../model/common/common_success_model.dart';
import '../utils/api_method.dart';
import '../utils/custom_snackbar.dart';

class ApiServices {
  static var client = http.Client();
  static Future<T?> apiService<T>(
    T Function(Map<String, dynamic>) fromJson,
    String apiEndpoint, {
    Map<String, dynamic>? body,
    String method = 'GET',
    int statusCode = 200,
    bool showResult = false,
    bool isBasic = false,
    bool showSuccessMessage = false,
    bool showErrorMessage = true,
  }) async {
    try {
      Map<String, dynamic>? mapResponse;

      if (method == 'POST') {
        mapResponse = await ApiMethod(isBasic: isBasic).post(
          apiEndpoint,
          body ?? {},
          code: statusCode,
          showErrorMessage: showErrorMessage,
        );
      } else if (method == 'GET') {
        mapResponse = await ApiMethod(isBasic: isBasic).get(
          apiEndpoint,
          showResult: showResult,
          showErrorMessage: showErrorMessage,
        );
      }
      if (mapResponse != null) {
        T result = fromJson(mapResponse);

        if (showSuccessMessage) {
          var messages = CommonSuccessModel.fromJson(mapResponse);
          CustomSnackBar.success(messages.message.success.first.toString());
        }

        return result;
      } else {
        return null;
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from ApiService ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went wrong!');
      return null;
    }
  }

  static Future<T?> multipartApiService<T>(
    T Function(Map<String, dynamic>) fromJson,
    String apiEndpoint,
    Map<String, String> body,
    List<String> fieldList,
    List<String> pathList, {
    int statusCode = 200,
    bool isBasic = false,
    bool showSuccessMessage = false,
    String tostTitle = 'Success',
  }) async {
    try {
      Map<String, dynamic>? mapResponse =
          await ApiMethod(isBasic: isBasic).multipartMultiFile(
        apiEndpoint,
        body,
        fieldList: fieldList,
        pathList: pathList,
      );

      if (mapResponse != null) {
        return _handleResponse<T>(
            mapResponse, fromJson, showSuccessMessage, tostTitle);
      }
    } catch (e) {
      log.e('🐞🐞🐞 err from multipartApiService ==> $e 🐞🐞🐞');
      CustomSnackBar.error('Something went wrong!');
    }
    return null;
  }

  static T? _handleResponse<T>(
    Map<String, dynamic> mapResponse,
    T Function(Map<String, dynamic>) fromJson,
    bool showSuccessMessage,
    String tostTitle,
  ) {
    T result = fromJson(mapResponse);

    if (showSuccessMessage) {
      var messages = CommonSuccessModel.fromJson(mapResponse);
      CustomSnackBar.success(
        messages.message.success.first.toString(),
      );
    }

    return result;
  }
}
