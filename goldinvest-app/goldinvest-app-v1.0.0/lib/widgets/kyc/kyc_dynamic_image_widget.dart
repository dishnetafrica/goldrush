import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:goldinvest/custom_assets/assets.gen.dart';
import 'package:image_picker/image_picker.dart';

import '../../controller/kyc/kyc_controller.dart';
import '../../languages/strings.dart';
import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';
import '../../utils/size.dart';
import '../common/others/custom_image_widget.dart';
import '../common/text_labels/title_heading4_widget.dart';
import '../image_picker/image_picker_sheet.dart';

File? imageFile;

class UpdateKycImageWidget extends StatefulWidget {
  const UpdateKycImageWidget(
      {super.key, required this.labelName, required this.fieldName});

  final String labelName;
  final String fieldName;

  @override
  State<UpdateKycImageWidget> createState() => _DropFileState();
}

class _DropFileState extends State<UpdateKycImageWidget> {
  final controller = Get.put(KycInformationController());

  Future pickImage(imageSource) async {
    try {
      final image =
          await ImagePicker().pickImage(source: imageSource, imageQuality: 50);
      if (image == null) return;

      imageFile = File(image.path);

      if (controller.listFieldName.isNotEmpty) {
        if (controller.listFieldName.contains(widget.fieldName)) {
          int itemIndex = controller.listFieldName.indexOf(widget.fieldName);
          controller.listFieldName[itemIndex] = widget.fieldName;
          controller.listImagePath[itemIndex] = imageFile!.path;
        } else {
          controller.listImagePath.add(imageFile!.path);
          controller.listFieldName.add(widget.fieldName);
        }
      } else {
        controller.listImagePath.add(imageFile!.path);
        controller.listFieldName.add(widget.fieldName);
      }
      setState(() {
        controller.updateImageData(widget.fieldName, imageFile!.path);
      });
      Get.back();
    } on PlatformException catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final imagePath = controller.getImagePath(widget.fieldName);

    return InkWell(
      onTap: () {
        _showImagePickerBottomSheet(context);
      },
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.2),
        ),
        child: Container(
          height: Dimensions.heightSize * 10,
          width: Dimensions.widthSize * 14.8,
          alignment: Alignment.center,
          decoration: imagePath == null
              ? BoxDecoration(
                  color: CustomColor.blackColor.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(Dimensions.radius),
                )
              : BoxDecoration(
                  borderRadius: BorderRadius.circular(Dimensions.radius),
                  image: DecorationImage(
                    fit: BoxFit.cover,
                    image: FileImage(File(imagePath)),
                  ),
                ),
          child: imagePath == null
              ? Column(
                  mainAxisAlignment: mainCenter,
                  children: [
                    CustomImageWidget(path: Assets.icon.documentCloud),
                    FittedBox(
                      fit: BoxFit.scaleDown,
                      child: TitleHeading4Widget(
                        text: Strings.frontPart,
                        color: CustomColor.blackColor.withValues(alpha: 0.2),
                      ),
                    ),
                  ],
                )
              : null, // Do not show children if an image is selected
        ),
      ),
    );
  }

  void _showImagePickerBottomSheet(BuildContext context) {
    showModalBottomSheet(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      context: context,
      builder: (BuildContext context) {
        return SizedBox(
          width: double.infinity,
          child: ImagePickerSheet(
            fromCamera: () {
              pickImage(ImageSource.camera);
            },
            fromGallery: () {
              pickImage(ImageSource.gallery);
            },
          ),
        );
      },
    );
  }
}
