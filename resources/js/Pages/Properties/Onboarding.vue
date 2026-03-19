<template>
  <div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-xl font-bold">تهيئة عقار جديد</h1>
      <p class="text-sm text-gray-500 mt-1">{{ property?.name || 'عقار جديد' }}</p>
    </div>

    <!-- Stepper -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <span class="text-sm font-medium text-gray-500">التقدم</span>
        <span class="text-sm font-bold text-primary-600">{{ progress }}%</span>
      </div>
      <div class="w-full bg-gray-100 rounded-full h-2 mb-6">
        <div class="bg-primary-600 h-2 rounded-full transition-all duration-500" :style="{ width: progress + '%' }"></div>
      </div>

      <div class="grid grid-cols-7 gap-2">
        <div
          v-for="(step, i) in steps"
          :key="step.key"
          class="flex flex-col items-center cursor-pointer"
          @click="currentStep === step.key || goToStep(step)"
        >
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center text-lg mb-2 transition-all"
            :class="{
              'bg-green-100 text-green-600': step.status === 'completed',
              'bg-primary-100 text-primary-600 ring-2 ring-primary-400': step.status === 'current',
              'bg-gray-100 text-gray-400': step.status === 'pending',
            }"
          >
            <span v-if="step.status === 'completed'">✓</span>
            <span v-else>{{ step.icon }}</span>
          </div>
          <span class="text-[10px] text-center leading-tight" :class="step.status === 'current' ? 'text-primary-600 font-bold' : 'text-gray-400'">
            {{ step.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">

      <!-- Step 1: بيانات العقار -->
      <div v-if="currentStep === 'property_info'">
        <h2 class="text-lg font-bold mb-4">🏢 بيانات العقار</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="input-label">اسم العقار *</label>
            <input v-model="form.name" type="text" class="input" placeholder="مثال: مجمع الواحة السكني">
          </div>
          <div>
            <label class="input-label">اسم العقار بالإنجليزي</label>
            <input v-model="form.name_en" type="text" class="input" dir="ltr" placeholder="Al Waha Compound">
          </div>
          <div>
            <label class="input-label">نوع العقار *</label>
            <select v-model="form.type" class="input">
              <option value="">اختر النوع</option>
              <option value="residential_compound">مجمع سكني</option>
              <option value="commercial_building">مبنى تجاري</option>
              <option value="tower">برج</option>
              <option value="villa">فيلا</option>
              <option value="mixed_use">متعدد الاستخدام</option>
              <option value="land">أرض</option>
              <option value="warehouse">مستودع</option>
              <option value="mall">مول تجاري</option>
              <option value="office_building">مبنى مكاتب</option>
            </select>
          </div>
          <div>
            <label class="input-label">المالك *</label>
            <select v-model="form.owner_id" class="input">
              <option value="">اختر المالك</option>
              <option v-for="o in owners" :key="o.id" :value="o.id">{{ o.name }}</option>
            </select>
          </div>
          <div>
            <label class="input-label">المدينة *</label>
            <input v-model="form.city" type="text" class="input" placeholder="الرياض">
          </div>
          <div>
            <label class="input-label">الحي</label>
            <input v-model="form.district" type="text" class="input" placeholder="النرجس">
          </div>
          <div class="md:col-span-2">
            <label class="input-label">العنوان *</label>
            <input v-model="form.address_line" type="text" class="input" placeholder="شارع الأمير سلطان، حي النرجس">
          </div>
          <div>
            <label class="input-label">سنة البناء</label>
            <input v-model="form.year_built" type="number" class="input" placeholder="2020">
          </div>
          <div>
            <label class="input-label">عدد الطوابق</label>
            <input v-model="form.floors_count" type="number" class="input" placeholder="4">
          </div>
          <div>
            <label class="input-label">المساحة الإجمالية (م²)</label>
            <input v-model="form.total_area_sqm" type="number" class="input" placeholder="3500">
          </div>
          <div>
            <label class="input-label">عدد المواقف</label>
            <input v-model="form.parking_spots" type="number" class="input" placeholder="30">
          </div>
          <div>
            <label class="input-label">رقم الصك</label>
            <input v-model="form.deed_number" type="text" class="input" dir="ltr">
          </div>
          <div>
            <label class="input-label">تاريخ الصك</label>
            <input v-model="form.deed_date" type="date" class="input">
          </div>
        </div>
        <div class="mt-6 flex gap-3">
          <button @click="submitPropertyInfo" class="btn-primary" :disabled="saving">
            {{ saving ? 'جاري الحفظ...' : 'حفظ والتالي ←' }}
          </button>
        </div>
      </div>

      <!-- Step 4: إنشاء الوحدات -->
      <div v-if="currentStep === 'units'">
        <h2 class="text-lg font-bold mb-4">🏠 إنشاء الوحدات</h2>
        <div class="bg-blue-50 rounded-lg p-4 mb-4">
          <p class="text-sm text-blue-700">تقدر تضيف الوحدات يدوي وحدة وحدة، أو بشكل مجمّع بتحديد عدد الطوابق والوحدات لكل طابق.</p>
        </div>

        <!-- Bulk Add -->
        <div class="border border-gray-200 rounded-lg p-4 mb-4">
          <h3 class="font-bold text-sm mb-3">إضافة مجمّعة</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
              <label class="input-label">عدد الطوابق</label>
              <input v-model="bulkForm.floors" type="number" class="input" min="1">
            </div>
            <div>
              <label class="input-label">وحدات لكل طابق</label>
              <input v-model="bulkForm.units_per_floor" type="number" class="input" min="1">
            </div>
            <div>
              <label class="input-label">نوع الوحدة</label>
              <select v-model="bulkForm.type" class="input">
                <option value="apartment">شقة</option>
                <option value="office">مكتب</option>
                <option value="shop">محل</option>
                <option value="studio">ستوديو</option>
              </select>
            </div>
            <div>
              <label class="input-label">الإيجار الأساسي</label>
              <input v-model="bulkForm.base_rent" type="number" class="input" placeholder="25000">
            </div>
          </div>
          <button @click="bulkAddUnits" class="btn-primary mt-3" :disabled="saving">
            إنشاء {{ (bulkForm.floors || 0) * (bulkForm.units_per_floor || 0) }} وحدة
          </button>
        </div>

        <!-- Units count -->
        <div v-if="unitsCount > 0" class="text-center p-4 bg-green-50 rounded-lg">
          <p class="text-2xl font-bold text-green-600">{{ unitsCount }}</p>
          <p class="text-sm text-green-600">وحدة تم إنشاؤها</p>
          <button @click="nextStep" class="btn-primary mt-3">التالي ←</button>
        </div>
      </div>

      <!-- Step 5: المعاينة الميدانية -->
      <div v-if="currentStep === 'inspection'">
        <h2 class="text-lg font-bold mb-4">🔍 المعاينة الميدانية</h2>
        <div class="space-y-3" v-if="checklist.length">
          <div
            v-for="item in checklist"
            :key="item.id"
            class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
          >
            <div>
              <p class="text-sm font-medium">{{ item.label }}</p>
              <p class="text-xs text-gray-400">{{ item.label_en }}</p>
            </div>
            <div class="flex gap-2">
              <button
                @click="setChecklistStatus(item.id, 'pass')"
                class="px-3 py-1 rounded-lg text-xs font-bold transition-all"
                :class="inspectionResults[item.id] === 'pass' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'"
              >✓ سليم</button>
              <button
                @click="setChecklistStatus(item.id, 'fail')"
                class="px-3 py-1 rounded-lg text-xs font-bold transition-all"
                :class="inspectionResults[item.id] === 'fail' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-400'"
              >✗ يحتاج</button>
              <button
                @click="setChecklistStatus(item.id, 'na')"
                class="px-3 py-1 rounded-lg text-xs font-bold transition-all"
                :class="inspectionResults[item.id] === 'na' ? 'bg-gray-500 text-white' : 'bg-gray-100 text-gray-400'"
              >— لا ينطبق</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <label class="input-label">التقييم العام (1-5)</label>
          <div class="flex gap-2 mt-1">
            <button
              v-for="n in 5" :key="n"
              @click="overallRating = n"
              class="w-10 h-10 rounded-lg text-lg transition-all"
              :class="overallRating >= n ? 'bg-amber-400 text-white' : 'bg-gray-100 text-gray-300'"
            >★</button>
          </div>
        </div>

        <div class="mt-4">
          <label class="input-label">ملاحظات</label>
          <textarea v-model="inspectionFindings" class="input" rows="3" placeholder="أي ملاحظات على حالة العقار..."></textarea>
        </div>

        <button @click="submitInspection" class="btn-primary mt-4" :disabled="saving">حفظ المعاينة ←</button>
      </div>

      <!-- Step 6: تقييم المخاطر -->
      <div v-if="currentStep === 'risk_assessment'">
        <h2 class="text-lg font-bold mb-4">⚠️ تقييم المخاطر</h2>
        <div class="space-y-3">
          <div v-for="(risk, i) in riskItems" :key="i" class="border border-gray-200 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label class="input-label">التصنيف</label>
                <select v-model="risk.category" class="input">
                  <option value="safety">سلامة</option>
                  <option value="operational">تشغيلي</option>
                  <option value="financial">مالي</option>
                  <option value="legal">قانوني</option>
                  <option value="compliance">امتثال</option>
                </select>
              </div>
              <div>
                <label class="input-label">الاحتمالية</label>
                <select v-model="risk.likelihood" class="input">
                  <option value="rare">نادر</option>
                  <option value="unlikely">غير محتمل</option>
                  <option value="possible">ممكن</option>
                  <option value="likely">محتمل</option>
                  <option value="certain">مؤكد</option>
                </select>
              </div>
              <div>
                <label class="input-label">الأثر</label>
                <select v-model="risk.impact" class="input">
                  <option value="negligible">لا يذكر</option>
                  <option value="minor">طفيف</option>
                  <option value="moderate">متوسط</option>
                  <option value="major">كبير</option>
                  <option value="catastrophic">كارثي</option>
                </select>
              </div>
            </div>
            <input v-model="risk.title" type="text" class="input mt-2" placeholder="وصف المخاطرة">
          </div>
        </div>
        <button @click="riskItems.push({category:'safety',likelihood:'possible',impact:'moderate',title:''})" class="btn-secondary mt-3">+ إضافة مخاطرة</button>
        <button @click="submitRiskAssessment" class="btn-primary mt-3 mr-2" :disabled="saving">حفظ التقييم ←</button>
      </div>

      <!-- Step 7: محضر التسليم -->
      <div v-if="currentStep === 'handover'">
        <h2 class="text-lg font-bold mb-4">✅ محضر التسليم والتشغيل</h2>
        <div class="bg-green-50 rounded-lg p-4 mb-4">
          <p class="text-sm text-green-700">هذي آخر خطوة — بعد التوقيع يتفعل العقار تلقائي.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="input-label">ممثل الشركة *</label>
            <input v-model="handoverForm.signed_by_company" type="text" class="input" placeholder="اسم ممثل الشركة">
          </div>
          <div>
            <label class="input-label">المالك أو ممثله *</label>
            <input v-model="handoverForm.signed_by_owner" type="text" class="input" placeholder="اسم المالك">
          </div>
          <div class="md:col-span-2">
            <label class="input-label">ملاحظات</label>
            <textarea v-model="handoverForm.notes" class="input" rows="3" placeholder="أي ملاحظات إضافية..."></textarea>
          </div>
        </div>
        <button @click="submitHandover" class="btn-primary mt-4" :disabled="saving">
          {{ saving ? 'جاري التفعيل...' : 'توقيع وتفعيل العقار ✓' }}
        </button>
      </div>

      <!-- Completed -->
      <div v-if="currentStep === null && progress === 100" class="text-center py-12">
        <div class="text-5xl mb-4">🎉</div>
        <h2 class="text-xl font-bold text-green-600 mb-2">تم تفعيل العقار بنجاح</h2>
        <p class="text-gray-500 mb-6">العقار جاهز للتشغيل — تقدر تبدأ تضيف عقود وتأجر الوحدات.</p>
        <a href="/properties" class="btn-primary">الذهاب للعقارات</a>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

const property = ref(null);
const steps = ref([]);
const progress = ref(0);
const currentStep = ref('property_info');
const saving = ref(false);
const owners = ref([]);
const unitsCount = ref(0);
const checklist = ref([]);
const inspectionResults = reactive({});
const overallRating = ref(0);
const inspectionFindings = ref('');

const form = reactive({
  name: '', name_en: '', type: '', owner_id: '',
  city: '', district: '', address_line: '',
  year_built: null, floors_count: null, total_area_sqm: null,
  parking_spots: null, deed_number: '', deed_date: '',
});

const bulkForm = reactive({
  floors: 4, units_per_floor: 6, type: 'apartment', base_rent: 25000,
});

const riskItems = ref([
  { category: 'safety', title: '', likelihood: 'possible', impact: 'moderate' },
]);

const handoverForm = reactive({
  signed_by_company: '', signed_by_owner: '', notes: '',
});

const setChecklistStatus = (id, status) => { inspectionResults[id] = status; };

const goToStep = (step) => {
  if (step.status !== 'pending') currentStep.value = step.key;
};

const nextStep = () => {
  const idx = steps.value.findIndex(s => s.key === currentStep.value);
  if (idx < steps.value.length - 1) currentStep.value = steps.value[idx + 1].key;
};

const updateStatus = (data) => {
  if (data.onboarding) {
    steps.value = data.onboarding.steps;
    progress.value = data.onboarding.progress;
    currentStep.value = data.onboarding.current_step;
  }
};

const submitPropertyInfo = async () => {
  saving.value = true;
  try {
    const { data } = await axios.post('/api/v1/onboarding/properties', form);
    property.value = data.data.property;
    updateStatus(data.data);
  } catch (e) { alert(e.response?.data?.message || 'حصل خطأ'); }
  finally { saving.value = false; }
};

const bulkAddUnits = async () => {
  saving.value = true;
  try {
    const { data } = await axios.post(`/api/v1/onboarding/properties/${property.value.id}/units/bulk`, bulkForm);
    unitsCount.value = data.data.total_units;
    updateStatus(data.data);
  } catch (e) { alert(e.response?.data?.message || 'حصل خطأ'); }
  finally { saving.value = false; }
};

const submitInspection = async () => {
  saving.value = true;
  try {
    const checklistData = checklist.value.map(c => ({
      id: c.id, status: inspectionResults[c.id] || 'na', notes: '',
    }));
    const { data } = await axios.post(`/api/v1/onboarding/properties/${property.value.id}/inspection`, {
      checklist: checklistData, overall_rating: overallRating.value,
      findings: inspectionFindings.value, recommendations: '',
    });
    updateStatus(data.data);
  } catch (e) { alert(e.response?.data?.message || 'حصل خطأ'); }
  finally { saving.value = false; }
};

const submitRiskAssessment = async () => {
  saving.value = true;
  try {
    const { data } = await axios.post(`/api/v1/onboarding/properties/${property.value.id}/risk-assessment`, {
      risks: riskItems.value.filter(r => r.title),
    });
    updateStatus(data.data);
  } catch (e) { alert(e.response?.data?.message || 'حصل خطأ'); }
  finally { saving.value = false; }
};

const submitHandover = async () => {
  saving.value = true;
  try {
    const { data } = await axios.post(`/api/v1/onboarding/properties/${property.value.id}/handover`, handoverForm);
    updateStatus(data.data);
    if (data.data.property_status === 'active') {
      progress.value = 100;
      currentStep.value = null;
    }
  } catch (e) { alert(e.response?.data?.message || 'حصل خطأ'); }
  finally { saving.value = false; }
};

onMounted(async () => {
  try { const { data } = await axios.get('/api/v1/owners'); owners.value = data.data || []; } catch {}
});
</script>
