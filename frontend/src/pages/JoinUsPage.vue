<script setup lang="ts">
import { useForm } from 'vue-formify';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import RadioButton from 'primevue/radiobutton';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { MemberApplicationRequests } from '@/services/requests/MemberApplicationRequests';
import type {
  ApplicationAnswers,
  ApplicationField,
  ApplicationFormSchema,
} from '@/types/applicationForm';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const { locale } = useLocale();
const toast = useToast();

// The questions live in the admin panel, not in this file. Keyed on the
// locale so switching language re-asks for the translated labels.
const { data: schema, isLoading } = useQuery<ApplicationFormSchema>({
  key: ['application-form', locale],
  request: () => MemberApplicationRequests.form(locale.value as string),
  cache: true,
  staleTime: 60,
  refetchTime: 600,
});

const { Form, values, reset } = useForm<ApplicationAnswers>({ initialValues: {} });
const status = ref<'idle' | 'submitting' | 'success' | 'error'>('idle');

const plusKeys = [
  'join.plus.english',
  'join.plus.knowledge',
  'join.plus.hours',
  'join.plus.inPerson',
  'join.plus.tools',
  'join.plus.tdk',
] as const;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/**
 * Give every question a starting value without discarding anything already
 * typed — the schema also arrives again on a locale switch, and losing a
 * half-filled form to a language change would be its own bug report.
 */
const syncValues = (next?: ApplicationFormSchema | null) => {
  if (!next) return;

  for (const section of next.sections) {
    for (const field of section.fields) {
      if (values.value[field.key] !== undefined) continue;
      values.value[field.key] = field.type === 'checkbox' ? [] : '';
    }
  }
};

const onSubmit = async () => {
  status.value = 'submitting';

  // Send only what was answered: the API validates against the same
  // questions, and an empty string for an optional question would be
  // stored as an answer nobody gave.
  const payload: ApplicationAnswers = {};
  for (const [key, value] of Object.entries(values.value)) {
    if (value === undefined || value === '' || (Array.isArray(value) && value.length === 0)) continue;
    payload[key] = value;
  }

  try {
    await MemberApplicationRequests.submit(payload);
    status.value = 'success';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch {
    status.value = 'error';
    toast.add({
      severity: 'error',
      summary: t('join.error'),
      life: 4000,
    });
  }
};

const resetForm = () => {
  reset();
  syncValues(schema.value);
  status.value = 'idle';
};

/** Two-digit card number, matching the 01 / 02 / 03 the form always had. */
const sectionNumber = (index: number) => String(index + 1).padStart(2, '0');

const isTextual = (field: ApplicationField) => field.type === 'text' || field.type === 'email';
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const submitting = computed(() => status.value === 'submitting');
const sections = computed(() => schema.value?.sections ?? []);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
watch(schema, syncValues, { immediate: true });
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <Toast />
  <HtmlTitle :title="t('join.title')" />

  <div
    class="container pt-[calc(var(--header-height)+32px)] pb-64px"
  >
    <div class="mb-32px">
      <SectionButton to="/">{{ t('button.home') }}</SectionButton>
    </div>

    <template v-if="status === 'success'">
      <div
        class="p-[clamp(32px,4vw,56px)] border border-cardBorder rounded-12px bg-cardBg text-center"
      >
        <h2
          class="m-0 mb-16px fs-[clamp(28px,5vw,40px)] uppercase"
        >
          {{ t('join.success.title') }}
        </h2>
        <p
          class="max-w-520px mx-auto mb-24px text-[var(--color-dimmed)] fs-16px lh-[1.6]"
        >
          {{ t('join.success.body') }}
        </p>
        <SectionButton type="button" @click="resetForm">
          {{ t('join.success.again') }}
        </SectionButton>
      </div>
    </template>

    <template v-else>
      <div
        class="relative p-[clamp(28px,4vw,56px)] mb-32px border border-cardBorder rounded-12px bg-cardBg overflow-hidden"
      >
        <div
          class="grid grid-cols-1 gap-32px md:(grid-cols-[minmax(0,1.5fr)_minmax(260px,1fr)] items-end)"
        >
          <div>
            <span
              class="inline-block py-4px px-12px mb-16px rounded-full text-primary bg-[rgb(242_172_60_/_18%)] font-600 fs-12px uppercase ls-[0.08em]"
            >
              {{ t('join.tagline') }}
            </span>
            <h1
              class="m-0 mb-16px text-primary fs-[clamp(40px,8vw,84px)] lh-[0.96] font-[var(--font-family-headline)] uppercase"
            >
              {{ t('join.title') }}
            </h1>
            <p
              class="max-w-[56ch] text-[var(--color-dimmed)] fs-17px lh-[1.6]"
            >
              {{ t('join.intro') }}
            </p>
          </div>
          <div
            class="p-20px border border-cardBorder rounded-8px bg-[rgb(255_255_255_/_4%)]"
          >
            <div
              class="mb-12px text-primary font-600 fs-12px uppercase ls-[0.08em]"
            >
              {{ t('join.plus.heading') }}
            </div>
            <ul class="flex flex-col gap-10px p-0 m-0 list-none">
              <li
                v-for="key in plusKeys"
                :key="key"
                class="flex items-start gap-10px fs-13px lh-[1.45]"
              >
                <span
                  class="inline-flex items-center justify-center shrink-0 w-18px h-18px mt-1px rounded-full text-primary bg-[rgb(242_172_60_/_22%)] fs-11px"
                >
                  ✓
                </span>
                <span>{{ t(key) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div
        v-if="isLoading && sections.length === 0"
        class="p-[clamp(24px,3vw,40px)] border border-cardBorder rounded-12px bg-cardBg text-[var(--color-dimmed)]"
      >
        {{ t('join.loading') }}
      </div>

      <Form v-else @submit="onSubmit">
        <div
          v-for="(section, index) in sections"
          :key="section.key"
          class="p-[clamp(24px,3vw,40px)] mb-32px border border-cardBorder rounded-12px bg-cardBg"
        >
          <div
            class="flex items-baseline gap-16px pb-16px mb-24px border-b border-dashed border-cardBorder"
          >
            <span
              class="join__section-num font-500 fs-32px lh-1"
            >{{ sectionNumber(index) }}</span>
            <h2
              class="m-0 font-600 fs-[clamp(20px,3vw,28px)] lh-1 uppercase"
            >
              {{ section.title }}
            </h2>
          </div>
          <p
            v-if="section.description"
            class="mt-0 mb-24px text-[var(--color-dimmed)] fs-14px lh-[1.6]"
          >
            {{ section.description }}
          </p>
          <div class="flex flex-col gap-16px">
            <div
              v-for="field in section.fields"
              :key="field.key"
              class="join__field flex flex-col gap-6px"
            >
              <label :for="`field-${field.key}`">
                {{ field.label }}<span v-if="field.required" class="ml-2px text-primary">*</span>
              </label>
              <small v-if="field.help" class="text-[var(--color-dimmed)]">{{ field.help }}</small>

              <InputText
                v-if="isTextual(field)"
                :id="`field-${field.key}`"
                v-model="(values[field.key] as string)"
                :type="field.type === 'email' ? 'email' : 'text'"
                :required="field.required"
                :maxlength="field.maxLength"
                :placeholder="field.placeholder ?? ''"
                class="w-full"
              />

              <Textarea
                v-else-if="field.type === 'textarea'"
                :id="`field-${field.key}`"
                v-model="(values[field.key] as string)"
                rows="3"
                auto-resize
                :required="field.required"
                :maxlength="field.maxLength"
                :placeholder="field.placeholder ?? ''"
                class="w-full"
              />

              <Select
                v-else-if="field.type === 'select'"
                :id="`field-${field.key}`"
                v-model="(values[field.key] as string)"
                :options="field.options"
                option-label="label"
                option-value="value"
                :placeholder="field.placeholder ?? ''"
                class="w-full"
              />

              <div
                v-else-if="field.type === 'radio'"
                class="grid grid-cols-1 gap-8px mt-8px sm:grid-cols-3"
                :class="{ 'sm:grid-cols-1': field.options.length > 3 }"
              >
                <label
                  v-for="option in field.options"
                  :key="option.value"
                  class="join__option-pill flex items-center gap-10px py-10px px-14px border border-cardBorder rounded-8px bg-[rgb(255_255_255_/_2%)] cursor-pointer transition-[border-color,background-color] duration-150"
                  :data-checked="values[field.key] === option.value"
                >
                  <RadioButton v-model="values[field.key]" :value="option.value" />
                  <span>{{ option.label }}</span>
                </label>
              </div>

              <div
                v-else-if="field.type === 'checkbox'"
                class="grid grid-cols-1 gap-8px mt-8px sm:grid-cols-3"
              >
                <label
                  v-for="option in field.options"
                  :key="option.value"
                  class="join__option-pill flex items-center gap-10px py-10px px-14px border border-cardBorder rounded-8px bg-[rgb(255_255_255_/_2%)] cursor-pointer transition-[border-color,background-color] duration-150"
                  :data-checked="((values[field.key] as string[]) ?? []).includes(option.value)"
                >
                  <Checkbox v-model="values[field.key]" :value="option.value" />
                  <span>{{ option.label }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div
          class="flex flex-col gap-16px mt-8px sm:(flex-row items-center justify-between)"
        >
          <SectionButton to="/">{{ t('button.home') }}</SectionButton>
          <SectionButton type="submit" :disabled="submitting">
            {{ submitting ? t('join.submitting') : t('join.submit') }}
          </SectionButton>
        </div>
      </Form>
    </template>
  </div>
</template>

<style lang="scss" scoped>
.join__section-num {
  color: transparent;
  background: linear-gradient(to right, transparent, var(--color-primary));
  background-clip: text;
}

.join__field {
  label {
    font-weight: 500;
    font-size: 14px;
  }

  small {
    color: var(--color-dimmed);
    font-size: 12px;
  }
}

.join__option-pill {
  &:hover {
    border-color: rgb(242 172 60 / 60%);
  }

  &[data-checked='true'] {
    border-color: var(--color-primary);
    background: rgb(242 172 60 / 12%);
  }
}
</style>
