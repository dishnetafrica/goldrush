import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../backend/model/kyc/kyc_info_model.dart';
import '../../languages/strings.dart';
import '../../utils/dimensions.dart';
import '../common/inputs/primary_input_widget.dart';
import 'kyc_dynamic_dropdown.dart';
import 'kyc_dynamic_image_widget.dart';

void getDynamicInputField({
  required List<InputField> data,
  required List<TextEditingController> inputFieldControllers,
  required RxList<dynamic> inputFields,
  required RxList<dynamic> inputFileFields,
  required RxBool hasFile,
  required RxString selectType,
}) {
  for (int item = 0; item < data.length; item++) {
    // Create a dynamic TextEditingController
    var textEditingController = TextEditingController();
    inputFieldControllers.add(textEditingController);

    // Build the appropriate widget based on the input field type
    if (data[item].type.contains('select')) {
      hasFile.value = true;
      selectType.value = data[item].validation.options.first.toString();
      textEditingController.text = selectType.value;

      var dropdownList = data[item].validation.options;
      inputFields.add(
        //   CustomDropdownMenu(
        //   itemsList: dropdownList,
        //   selectMethod: selectType,
        //   hintText: data[item].label,
        //   onChanged: (value) {
        //     selectType.value = value!;
        //     textEditingController.text = selectType.value;
        //     debugPrint(selectType.value);
        //   },
        // )
        KycDynamicDropDown(
          label: data[item].label,
          selectMethod: selectType,
          itemsList: dropdownList,
          onChanged: (value) {
            selectType.value = value!;
            textEditingController.text = selectType.value;
            debugPrint(selectType.value);
          },
        ),
      );
    } else if (data[item].type.contains('file')) {
      hasFile.value = true;
      inputFileFields.add(
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(height: Dimensions.heightSize),
            UpdateKycImageWidget(
              labelName: data[item].label,
              fieldName: data[item].name,
            ),
          ],
        ),
      );
    } else if (data[item].type.contains('text')) {
      inputFields.add(
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(height: Dimensions.heightSize),
            PrimaryInputWidget(
              textController: textEditingController,
              hintText: '${Strings.submit} ${data[item].label}',
              // label: data[item].label,
            ),
          ],
        ),
      );
    }
  }
}
