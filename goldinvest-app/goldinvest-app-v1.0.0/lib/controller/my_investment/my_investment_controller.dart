import 'package:get/get.dart';
import 'package:goldinvest/backend/model/my_investments/my_investmnets_model.dart';

import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class MyInvestmentController extends GetxController {
  List<Invest>? investments = [];
  RxString status = "".obs;

  @override
  void onInit() {
    myInvestmentsProcess();

    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late MyInvestmentsModel _myInvestmentModel;
  MyInvestmentsModel get myInvestments => _myInvestmentModel;

  Future<MyInvestmentsModel?> myInvestmentsProcess() async {
    return RequestProcess().request<MyInvestmentsModel>(
      showResult: true,
      fromJson: MyInvestmentsModel.fromJson,
      apiEndpoint: ApiEndpoint.myInvestment,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _myInvestmentModel = value!;
        _setData(_myInvestmentModel);
      },
    );
  }

  void _setData(MyInvestmentsModel myInvestmentModel) {
    var data = _myInvestmentModel.data;
    investments = data.invest;
    status.value = data.instructions.status;
  }

  var selectedIndex = 0.obs;

  void setTabIndex(int index) {
    selectedIndex.value = index;
  }
}
