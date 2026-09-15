import 'package:get/get.dart';

import '../../backend/model/gold_store/gold_store_model.dart';
import '../../backend/services/api_endpoint.dart';
import '../../backend/utils/request_process.dart';

class GoldStoreController extends GetxController {
  RxString goldName = "".obs;
  RxDouble goldPrice = 0.00.obs;
  RxString goldWeight = "".obs;
  RxString goldPurity = "".obs;
  RxString goldType = "".obs;
  RxString goldManufacturer = "".obs;
  RxString goldOrigin = "".obs;
  RxString goldImage = "".obs;
  RxString selectSlug = "".obs;
  RxInt selectedIndex = 0.obs;
  List<Gold>? golds = [];

  static final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;
  late GoldStoreModel _goldSoreModel;
  GoldStoreModel get goldStoreModel => _goldSoreModel;

  @override
  void onInit() {
    goldStoreInfoProcess();

    super.onInit();
  }

  Future<GoldStoreModel?> goldStoreInfoProcess() async {
    return RequestProcess().request<GoldStoreModel>(
      showResult: true,
      fromJson: GoldStoreModel.fromJson,
      apiEndpoint: ApiEndpoint.goldStore,
      isLoading: _isLoading,
      method: HttpMethod.GET,
      showSuccessMessage: false,
      onSuccess: (value) {
        _goldSoreModel = value!;

        _setData(_goldSoreModel);
      },
    );
  }

  void _setData(GoldStoreModel goldStoreModel) {
    var data = _goldSoreModel.data;
    golds = data.golds;
    goldImage.value =
        "${goldStoreModel.data.baseUrl}/${goldStoreModel.data.imagePath}/${golds!.first.image}";

    selectSlug.value = golds!.first.slug;
  }
}
