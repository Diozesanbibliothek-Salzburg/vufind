import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collection;
import java.util.LinkedHashSet;
import java.util.List;

import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;

public class Default {
	
	
public Collection<String> useDefaultIfSubfieldMissing(Record record, String fieldNumber, String subfieldCode, String defaultValue) {
		
		Collection<String> returnValue = new ArrayList<String>();
		
		// Get all fields with the specified field number
		List<VariableField> variableFields = record.getVariableFields(fieldNumber);

		// Use the default value if there are no fields with the specified field number
		if (variableFields == null || variableFields.isEmpty()) {
			returnValue.add(defaultValue);
			return returnValue;
		}
		
		// If there are fields with the specified field number, iterate over them
		for (VariableField variableField : variableFields) {
			
			// Check if the current field is a datafield. If yes, check for the given subfield.
			if (variableField instanceof DataField) {
				DataField dataField = (DataField)variableField;
				
				List<Subfield> subfields = new ArrayList<Subfield>();
				List<String> subfieldCodes = Arrays.asList(subfieldCode.split("\\s*:\\s*"));
				for (String subfieldCodeStr : subfieldCodes) {
					List<Subfield> currentSubfields = dataField.getSubfields(subfieldCodeStr.charAt(0));
					subfields.addAll(currentSubfields);
				}
				
				// If there is no given subfield, return the default value
				if (subfields == null || subfields.isEmpty()) {
					returnValue.add(defaultValue);
					return returnValue;
				} else {
					for (Subfield subfield : subfields) {
						String subfieldValue = subfield.getData();
						returnValue.add(subfieldValue);
					}
				}
			} else if (variableField instanceof ControlField) {
				ControlField controlField = (ControlField)variableField;
				returnValue.add(controlField.getData());
			}
		}

		return returnValue;
	}


	public Collection<String> useDefaultIfSubfieldMissing(Record record, String fieldNumber, String subfieldCode, String defaultValue, String uniqueStr) {
		
		Collection<String> returnValue = new ArrayList<String>();
		
		// If "unique" is set to "true", use a LinkedHashSet for not returning duplicates.
		if (uniqueStr != null && uniqueStr.equals("true")) {
			returnValue = new LinkedHashSet<>();
		}
		
		// Get all fields with the specified field number
		List<VariableField> variableFields = record.getVariableFields(fieldNumber);

		// Use the default value if there are no fields with the specified field number
		if (variableFields == null || variableFields.isEmpty()) {
			returnValue.add(defaultValue);
			return returnValue;
		}		
		
		// If there are fields with the specified field number, iterate over them
		for (VariableField variableField : variableFields) {
			
			// Check if the current field is a datafield. If yes, check for the given subfield.
			if (variableField instanceof DataField) {
				DataField dataField = (DataField)variableField;

				List<Subfield> subfields = new ArrayList<Subfield>();
				List<String> subfieldCodes = Arrays.asList(subfieldCode.split("\\s*:\\s*"));
				for (String subfieldCodeStr : subfieldCodes) {
					List<Subfield> currentSubfields = dataField.getSubfields(subfieldCodeStr.charAt(0));
					subfields.addAll(currentSubfields);
				}
							
				// If there is no given subfield, return the default value
				if (subfields == null || subfields.isEmpty()) {
					returnValue.add(defaultValue);
					return returnValue;
				} else {
					for (Subfield subfield : subfields) {
						String subfieldValue = subfield.getData();
						returnValue.add(subfieldValue);
					}
				}
			} else if (variableField instanceof ControlField) {
				ControlField controlField = (ControlField)variableField;
				returnValue.add(controlField.getData());
			}
		}

		return returnValue;
	}
	
	
	public Collection<String> useDefaultIfSubfieldExists(Record record, String fieldNumbers, String subfieldCode, String subfieldExistsCode, String defaultValue) {
		Collection<String> returnValue = new ArrayList<String>();
		
		String[] fieldNumbersArr = fieldNumbers.split("\\s*:\\s*");
		
		// Get all fields with the specified field number
		List<VariableField> variableFields = record.getVariableFields(fieldNumbersArr);

		// Return null if there are no fields with the specified field number. We only use the default value
		// if a certain subfield exists.
		if (variableFields == null || variableFields.isEmpty()) {
			return null;
		}
		
		// If there are fields with the specified field number, iterate over them
		for (VariableField variableField : variableFields) {

			// Check if the current field is a datafield. If yes, check for the given subfield.
			if (variableField instanceof DataField) {
				DataField dataField = (DataField)variableField;
				
				List<Subfield> subfields = new ArrayList<Subfield>();
				List<String> subfieldCodes = Arrays.asList(subfieldCode.split("\\s*:\\s*"));
				for (String subfieldCodeStr : subfieldCodes) {
					List<Subfield> currentSubfields = dataField.getSubfields(subfieldCodeStr.charAt(0));
					subfields.addAll(currentSubfields);
				}
				
				// Check if another subfield must exist for adding a default value
				List<Subfield> subfieldsToCheck = dataField.getSubfields(subfieldExistsCode.charAt(0));
				if ((subfields == null ||  subfields.isEmpty())) {
					if (subfieldsToCheck != null && !subfieldsToCheck.isEmpty()) {
						returnValue.add(defaultValue);
					}
				} else {
					for (Subfield subfield : subfields) {
						String subfieldValue = subfield.getData();
						returnValue.add(subfieldValue);
					}
				}
				
			} else if (variableField instanceof ControlField) {
				ControlField controlField = (ControlField)variableField;
				returnValue.add(controlField.getData());
			}
		}

		return returnValue;
	}
	
}

