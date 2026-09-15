import 'package:get/get.dart';
import '../../widgets/common/custom_drop_down.dart/custom_drop_down.dart';

class DropdownController<T extends DropdownModel> extends GetxController {
  var selectedItem = Rx<T?>(null);
  var isDropdownVisible = false.obs;

  void selectItem(T? item) {
    selectedItem.value = item;
    hideDropdown();
  }

  void toggleDropdown() {
    isDropdownVisible.value = !isDropdownVisible.value;
  }

  void hideDropdown() {
    isDropdownVisible.value = false;
  }
}
