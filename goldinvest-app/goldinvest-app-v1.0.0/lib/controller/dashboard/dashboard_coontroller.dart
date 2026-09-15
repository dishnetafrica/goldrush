import 'package:get/get.dart';
import 'package:goldinvest/backend/model/dashboard/dashboard_model.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class DashboardController extends GetxController {
  RxDouble currentBalance = 0.0.obs;
  RxString currencyCode = "".obs;
  RxDouble investmentAmount = 0.0.obs;
  RxDouble totalProfit = 0.0.obs;
  RxBool isFirst = true.obs;
  RxBool isLoggedIn = true.obs;

  static final _isLoading = true.obs;
  static bool get isLoading => _isLoading.value;
  late DashboardModel _dashboardModel;
  DashboardModel get profileInfoModel => _dashboardModel;

  Future<DashboardModel?> get onProfileInfo => dashboardInfoProcess();
  @override
  void onInit() {
    getDashboardDataStream();

    super.onInit();
  }

  Stream<DashboardModel?> getDashboardDataStream() async* {
    while (isLoggedIn.value) {
      await Future.delayed(Duration(seconds: isFirst.value ? 0 : 2));
      if (isLoggedIn.value) {
        DashboardModel? data = await dashboardInfoProcess();
        isFirst.value = false;
        yield data;
      }
    }
  }

  Future<DashboardModel?> dashboardInfoProcess() async {
    return RequestProcess().request<DashboardModel>(
      showResult: true,
      fromJson: DashboardModel.fromJson,
      apiEndpoint: ApiEndpoint.dashboard,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _dashboardModel = value!;
        LocalStorage.save(
            baseCurrencyCode: _dashboardModel.data.wallets.first.currency.code);

        _setData(_dashboardModel);
      },
    );
  }

  void _setData(DashboardModel dashboardModel) {
    var data = _dashboardModel.data;
    currentBalance.value = data.wallets.first.balance;
    currencyCode.value = LocalStorage.baseCurrencyCode;

    if (data.investAmount is String) {
      investmentAmount.value = double.parse(data.investAmount);
    } else if (data.investAmount is int) {
      investmentAmount.value = data.investAmount.toDouble();
    }

    totalProfit.value = double.parse(data.profitAmount.toString());
  }
}
