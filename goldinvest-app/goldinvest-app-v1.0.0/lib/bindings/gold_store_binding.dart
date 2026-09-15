import 'package:get/get.dart';
import 'package:goldinvest/controller/gold/gold_store_controller.dart';

class GoldStoreBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut(() => GoldStoreController());
  }
}
