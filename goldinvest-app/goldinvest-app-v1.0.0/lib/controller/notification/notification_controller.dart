import 'package:get/get.dart';

import '../../backend/model/notification/notification_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class NotificationController extends GetxController {
  RxString notificationTitle = "".obs;
  RxString notificationMassage = "".obs;

  List<Notification>? notifications = [];

  @override
  void onInit() {
    notificationProcess();

    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late NotificationModel _notificationModel;
  NotificationModel get notificationModel => _notificationModel;

  Future<NotificationModel?> notificationProcess() async {
    return RequestProcess().request<NotificationModel>(
      showResult: true,
      fromJson: NotificationModel.fromJson,
      apiEndpoint: ApiEndpoint.notification,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _notificationModel = value!;
        _setData(_notificationModel);
      },
    );
  }

  void _setData(NotificationModel notificationModel) {
    var data = _notificationModel.data;
    notifications = data.notifications;
  }
}
