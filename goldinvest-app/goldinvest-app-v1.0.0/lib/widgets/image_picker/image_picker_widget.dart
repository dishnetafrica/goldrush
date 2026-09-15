import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:goldinvest/controller/profile/profile_controller.dart';
import 'package:image_picker/image_picker.dart';

import '../../utils/dimensions.dart';

class ImagePickerWidget extends StatelessWidget {
  ImagePickerWidget({super.key, required this.child});
  final Widget child;

  final controller = Get.put(ProfileController());

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
        onTap: () {
          showModalBottomSheet(
              context: context,
              builder: (context) => imagePickerBottomSheet(context));
        },
        child: child);
  }

  Container imagePickerBottomSheet(BuildContext context) {
    return Container(
      width: double.infinity,
      height: MediaQuery.of(context).size.height * 0.15,
      margin: EdgeInsets.all(Dimensions.marginSizeVertical * 0.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            child: IconButton(
                onPressed: () {
                  Get.back();
                  controller.pickImage(ImageSource.gallery);
                },
                icon: Icon(
                  Icons.image,
                  color: Theme.of(context).primaryColor,
                  size: 50,
                )),
          ),
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            child: IconButton(
                onPressed: () {
                  Get.back();
                  controller.pickImage(ImageSource.camera);
                },
                icon: Icon(
                  Icons.camera,
                  color: Theme.of(context).primaryColor,
                  size: 50,
                )),
          ),
        ],
      ),
    );
  }
}
