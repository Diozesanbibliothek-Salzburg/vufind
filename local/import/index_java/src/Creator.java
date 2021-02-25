import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collection;
import java.util.Collections;
import java.util.Iterator;
import java.util.LinkedHashSet;
import java.util.LinkedList;
import java.util.List;
import org.apache.log4j.Logger;

import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;

import org.solrmarc.tools.SolrMarcIndexerException;

public class Creator {

    static Logger logger = Logger.getLogger(Creator.class.getName());

    /**
     * Get secondary authors. If no corresponding 100/110/111 field exists, the first 700/710/711 field will be removed
     * as it will already be used for the primary author.
     * 
     * @param  record        Marc record object.
     * @param  fieldNo       Field no. of secondary author. Can only be 700, 710 or 711.
     * @param  ind1          Indicator 1 to check for
     * @param  ind2          Indicator 2 to check for
     * @param  subfieldCodes Subfield codes to use
     * @param  separator     Separator for subfield values
     * 
     * @return Collection&lt;String&gt; or null
     */
    public Collection<String> getSecondaryAuthors(Record record, String fieldNo, String ind1, String ind2,
        String subfieldCodes, String separator) {

        // Get variable fields of secondary authors with given field number
        List<VariableField> secondaryAuthorFields = record.getVariableFields(fieldNo);

        // Skip if there are no secondary author fields with the given field number
        if (secondaryAuthorFields == null || secondaryAuthorFields.isEmpty()) {
            return null;
        }

        // List of allowed field numbers
        List<String> allowedFields = Arrays.asList("700", "710", "711");

        // Check for correct field number
        if (!allowedFields.contains(fieldNo)) {
            String errorMsg = "Given field number must be 700, 710 or 711.";
            logger.error(errorMsg);
            throw new SolrMarcIndexerException(SolrMarcIndexerException.EXIT, errorMsg);
        }

        // Create return value
        Collection<String> returnValue = new ArrayList<String>();

        // Get primary author field number
        String primaryAuthorFieldNo = fieldNo.replaceFirst("7", "1");

        // Get primary author field
        List<VariableField> primaryAuthorField = record.getVariableFields(primaryAuthorFieldNo);

        // Check if primary author field exists
        if (primaryAuthorField == null || primaryAuthorField.isEmpty()) {
            // If no primary author exists, the first secondary author was already treated as primary author and was
            // indexed to the "author(_...)" field. That is why the first secondary author should be removed here so
            // that the name will not be indexed again into "author2(_...)" field(s).
            secondaryAuthorFields.remove(0);
        }


        // Check for indicator 1 and indicator 2
        ind1 = (ind1 != null && ind1.equals("null")) ? null : ind1;
        ind2 = (ind2 != null && ind2.equals("null")) ? null : ind2;

        // Check for separator and use space as default
        separator = (separator != null && !separator.isEmpty()) ? separator : " "; 

        // Get the given subfields and return them as a Collection<String>
        for (VariableField secondaryAuthorField : secondaryAuthorFields) {
            if (secondaryAuthorField instanceof DataField) {
                // Convert VariableField to DataField.
                DataField datafield = (DataField)secondaryAuthorField;

                // Check for given indicators in the datafield
                boolean skip = false;
                if (ind1 != null && datafield.getIndicator1() != ind1.charAt(0)) {
                    skip = true;
                }
                if (ind2 != null && datafield.getIndicator2() != ind2.charAt(0)) {
                    skip = true;
                }

                // Skip field if appropriate
                if (!skip) {
                    ArrayList<String> subfieldValues = new ArrayList<String>();
                    for (int i = 0; i < subfieldCodes.length(); i++){
                        char subfieldCode = subfieldCodes.charAt(i);
                        Subfield subfield = datafield.getSubfield(subfieldCode);
                        if (subfield != null) {
                            subfieldValues.add(subfield.getData().trim());
                        }
                    }
                    returnValue.add(String.join(separator, subfieldValues));
                }
            }
        }

        return returnValue;
    }
}